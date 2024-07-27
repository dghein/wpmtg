document.addEventListener("DOMContentLoaded", (e) => {
  const wpmtgImportSetForm = document.querySelector('#frmImport');

  if (wpmtgImportSetForm) {
    wpmtgImportSetForm.addEventListener("submit", (e) => {
      e.preventDefault();
  
      fetch('/wp/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(new FormData(wpmtgImportSetForm))
      }).then(response => {
        return response.text();
      }).then(data => {
        console.log(data);
        const form = document.querySelector('#frmImport');
        const newDiv = document.createElement('div');
        newDiv.innerHTML = data;
        form.append(newDiv);
      })
    });
  }
});