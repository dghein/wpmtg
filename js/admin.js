/**
 * Card Importer admin form handler.
 *
 * Works alongside the React set selector (src/admin-set-selector/index.jsx).
 * This file handles import-method toggling, AJAX submission, and locking the UI
 * during import via custom events the React component listens for.
 */
document.addEventListener('DOMContentLoaded', () => {
  const wpmtgImportSetForm = document.querySelector('#frmImport');

  if (!wpmtgImportSetForm) {
    return;
  }

  const wpmtgImportMethods = document.querySelectorAll('.card-import-form__import-methods');
  const allInputs = document.querySelectorAll('.card-import-form__input');
  const hiddenSetInput = document.querySelector('#importFormFieldSetCode');

  /** Tell the React set selector to clear its search field and hidden input value. */
  const resetSetSelector = () => {
    document.dispatchEvent(new CustomEvent('wpmtg-set-selector-reset'));
  };

  /** Lock or unlock the React set selector during AJAX import. */
  const setSetSelectorDisabled = (isDisabled) => {
    document.dispatchEvent(
      new CustomEvent(isDisabled ? 'wpmtg-set-selector-disable' : 'wpmtg-set-selector-enable')
    );
  };

  /** Clear the value of a form field group (wrapper div or plain input). */
  const clearInputGroup = (inputGroup) => {
    const setField = inputGroup.querySelector('#importFormFieldSetCode');
    const dateField = inputGroup.querySelector('#importFormFieldSetDate');

    if (setField) {
      setField.value = '';
      resetSetSelector();
      return;
    }

    if (dateField) {
      dateField.value = '';
    }
  };

  // Show only the first import method on page load (set code is checked by default).
  allInputs.forEach((input, index) => {
    if (index !== 0) {
      input.style.display = 'none';
    }
  });

  // Switch between set-code (React selector) and date import fields.
  wpmtgImportMethods.forEach((item) => {
    item.addEventListener('click', () => {
      const inputId = item.dataset.toggle;
      const activeInput = document.querySelector(`#${inputId}`);

      allInputs.forEach((inputGroup) => {
        inputGroup.style.display = 'none';
        clearInputGroup(inputGroup);
      });

      activeInput.style.display = 'block';
    });
  });

  wpmtgImportSetForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const formInputDate = document.querySelector('#importFormFieldSetDate');
    const formInputSubmit = document.querySelector('#importFormSubmitButton');
    const requestStatusContainer = document.querySelector('.wpmtg-request-status');

    if (requestStatusContainer) {
      requestStatusContainer.innerHTML = '';
    }

    const statusDiv = document.createElement('div');
    statusDiv.setAttribute('class', 'wpmtg-request-status');

    const loadingImage = document.createElement('img');
    loadingImage.src = `${localizedVars.pluginPath}/images/loading.gif`;
    loadingImage.width = 16;
    loadingImage.height = 16;
    loadingImage.alt = 'loading';
    statusDiv.appendChild(loadingImage);

    const statusMessage = document.createElement('span');
    statusMessage.textContent = 'Importing cards. Please wait...';
    statusDiv.appendChild(statusMessage);

    wpmtgImportSetForm.append(statusDiv);

    // Disable inputs until the import finishes.
    formInputDate.setAttribute('readonly', 'readonly');
    formInputSubmit.setAttribute('disabled', 'disabled');
    setSetSelectorDisabled(true);

    fetch('/wp/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(new FormData(wpmtgImportSetForm)),
    })
      .then((response) => response.text())
      .then((data) => {
        statusDiv.innerHTML = data;
        formInputDate.removeAttribute('readonly');
        formInputSubmit.removeAttribute('disabled');
        setSetSelectorDisabled(false);

        // Clear set selection after a successful import so the user starts fresh.
        if (hiddenSetInput) {
          hiddenSetInput.value = '';
        }
        resetSetSelector();
      });
  });
});
