<?php

namespace Wpmtg;

class WpmtgPackOpener
{
    /** @var array<int, int> rare/mythic count => weight (sums to 100) */
    private const RARE_COUNT_WEIGHTS = [
        1 => 58,
        2 => 37,
        3 => 4,
        4 => 1,
    ];

    private const MYTHIC_SLOT_CHANCE = 0.125;

    private const POOL_TRANSIENT_TTL = HOUR_IN_SECONDS;

    /**
     * Generate a simulated booster pack for the given set.
     *
     * @return array{cards: array<int, array<string, mixed>>}|\WP_Error
     */
    public function generatePack(string $setSlug)
    {
        $setSlug = sanitize_title($setSlug);

        if ($setSlug === '') {
            return new \WP_Error('invalid_set', __('A valid card set is required.', 'wpmtg'), ['status' => 400]);
        }

        $term = get_term_by('slug', $setSlug, 'wpmtg_card_setname');

        if (!$term || is_wp_error($term)) {
            return new \WP_Error('unknown_set', __('That card set was not found.', 'wpmtg'), ['status' => 404]);
        }

        $pools = $this->getCardPools($setSlug);

        if ($this->isPoolEmpty($pools)) {
            return new \WP_Error('empty_set', __('No cards are available for this set.', 'wpmtg'), ['status' => 404]);
        }

        $usedNames = [];
        $cards = [];

        $rareCount = $this->rollRareCount();

        for ($i = 0; $i < $rareCount; $i++) {
            $card = $this->pickRareSlot($pools, $usedNames);

            if ($card) {
                $cards[] = $card;
            }
        }

        for ($i = 0; $i < 3; $i++) {
            $card = $this->pickFromPool($pools['uncommon'], $usedNames);

            if ($card) {
                $cards[] = $card;
            }
        }

        $commonCount = $rareCount === 1 ? 7 : 6;

        for ($i = 0; $i < $commonCount; $i++) {
            $card = $this->pickFromPool($pools['common'], $usedNames);

            if ($card) {
                $cards[] = $card;
            }
        }

        $land = $this->pickFromPool($pools['basic_land'], $usedNames);

        if ($land) {
            $cards[] = $land;
        }

        return [
            'cards' => $cards,
            'set' => [
                'slug' => $term->slug,
                'name' => $term->name,
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, int>>>
     */
    private function getCardPools(string $setSlug): array
    {
        $transientKey = 'wpmtg_pool_' . $setSlug;
        $cached = get_transient($transientKey);

        if (is_array($cached)) {
            return $cached;
        }

        $pools = [
            'mythic' => [],
            'rare' => [],
            'uncommon' => [],
            'common' => [],
            'basic_land' => [],
        ];

        $query = new \WP_Query([
            'post_type' => 'wpmtg_magiccard',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => 'wpmtg_card_setname',
                    'field' => 'slug',
                    'terms' => $setSlug,
                ],
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]);

        foreach ($query->posts as $postId) {
            $postId = (int) $postId;
            $name = get_the_title($postId);
            $type = (string) get_post_meta($postId, 'type', true);
            $rarity = (string) get_post_meta($postId, 'rarity', true);

            if ($name === '') {
                continue;
            }

            if (stripos($type, 'Basic Land') !== false) {
                $pools['basic_land'][$name][] = $postId;
                continue;
            }

            if (!isset($pools[$rarity]) || !is_array($pools[$rarity])) {
                continue;
            }

            $pools[$rarity][$name][] = $postId;
        }

        set_transient($transientKey, $pools, self::POOL_TRANSIENT_TTL);

        return $pools;
    }

    /**
     * @param array<string, array<string, array<int, int>>> $pools
     */
    private function isPoolEmpty(array $pools): bool
    {
        foreach ($pools as $pool) {
            if (!empty($pool)) {
                return false;
            }
        }

        return true;
    }

    private function rollRareCount(): int
    {
        $roll = random_int(1, 100);
        $cumulative = 0;

        foreach (self::RARE_COUNT_WEIGHTS as $count => $weight) {
            $cumulative += $weight;

            if ($roll <= $cumulative) {
                return $count;
            }
        }

        return 1;
    }

    /**
     * @param array<string, array<string, array<int, int>>> $pools
     * @param array<string, bool> $usedNames
     */
    private function pickRareSlot(array $pools, array &$usedNames): ?array
    {
        $tryMythic = (random_int(0, 999) / 1000) < self::MYTHIC_SLOT_CHANCE;

        if ($tryMythic) {
            $card = $this->pickFromPool($pools['mythic'], $usedNames);

            if ($card) {
                return $card;
            }
        }

        $card = $this->pickFromPool($pools['rare'], $usedNames);

        if ($card) {
            return $card;
        }

        if (!$tryMythic) {
            return $this->pickFromPool($pools['mythic'], $usedNames);
        }

        return null;
    }

    /**
     * @param array<string, array<int, int>> $pool
     * @param array<string, bool> $usedNames
     */
    private function pickFromPool(array $pool, array &$usedNames): ?array
    {
        $availableNames = array_filter(
            array_keys($pool),
            static function ($name) use ($usedNames) {
                return !isset($usedNames[$name]);
            }
        );

        if ($availableNames === []) {
            return null;
        }

        $name = $availableNames[array_rand($availableNames)];
        $postIds = $pool[$name];
        $postId = $postIds[array_rand($postIds)];
        $usedNames[$name] = true;

        return $this->formatCard((int) $postId);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCard(int $postId): array
    {
        $image = get_post_meta($postId, 'card_image', true);

        if (!$image && has_post_thumbnail($postId)) {
            $image = get_the_post_thumbnail_url($postId, 'full');
        }

        return [
            'id' => $postId,
            'name' => get_the_title($postId),
            'rarity' => (string) get_post_meta($postId, 'rarity', true),
            'image' => $image ? (string) $image : '',
            'permalink' => get_permalink($postId) ?: '',
        ];
    }
}
