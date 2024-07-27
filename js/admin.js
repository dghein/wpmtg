document.addEventListener("DOMContentLoaded", (e) => {
  const wpmtgImportSetForm = document.querySelector('#frmImport');
  wpmtgImportSetForm.addEventListener("submit", (e) => {
    e.preventDefault();

    fetch('/wp/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams(new FormData(wpmtgImportSetForm))
    }).then(response => {
      return response;
    }).then(jsonResponse => {
      console.log(jsonResponse);
      const form = document.querySelector('#frmImport');
      const newDiv = document.createElement('div');
      newDiv.innerHTML = 'You imported some cards with an ajax request!';
      form.append(newDiv);
    })
  });
});