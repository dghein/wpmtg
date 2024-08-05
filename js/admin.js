document.addEventListener("DOMContentLoaded", (e) => {
  /**
   * WP Admin WPMTG Card Importer tool
   */
  const wpmtgImportSetForm = document.querySelector('#frmImport');
  
  if (wpmtgImportSetForm) {
    const wpmtgImportMethods = document.querySelectorAll('.card-import-form__import-methods');
    const allInputs = document.querySelectorAll('.card-import-form__input');

    // hide all the inputs except the first 
    allInputs.forEach((input, index) => {
      if (index !== 0) {
        input.style.display = "none";
      }
    });

    // show/hide inputs based on the input method that has been selected
    wpmtgImportMethods.forEach(item => {
      item.addEventListener('click', (e) => {
        // show corresponding input field
        let inputId = item.dataset.toggle;
        let thisInput = document.querySelector(`#${inputId}`);

        allInputs.forEach(input => {
          input.style.display = "none";
          input.value = '';
        });

        thisInput.style.display = "inline-block";
      })
    })

    // handle card import form submission
    wpmtgImportSetForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const form = document.querySelector('#frmImport');
      const formInputSet = document.querySelector('#importFormFieldSetCode');
      const formInputDate = document.querySelector('#importFormFieldSetDate');
      const formInputSubmit = document.querySelector('#importFormSubmitButton');
      const requestStatusContainer = document.querySelector('.wpmtg-request-status');

      if (requestStatusContainer) {
        requestStatusContainer.innerHTML = '';
      }

      // container to display the request status
      const newDiv = document.createElement('div');
      newDiv.setAttribute('class', 'wpmtg-request-status');

      // build loading spinner
      const imgLoadingAnimation = document.createElement('img');
      imgLoadingAnimation.src = `${localizedVars.pluginPath}/images/loading.gif`;
      imgLoadingAnimation.width = 16;
      imgLoadingAnimation.height = 16;
      imgLoadingAnimation.alt = 'loading';
      newDiv.appendChild(imgLoadingAnimation);

      // build request status message
      const statusMessageContainer = document.createElement('span');
      let statusMessage = document.createTextNode(`Importing cards. Please wait...`);
      statusMessageContainer.appendChild(statusMessage);
      newDiv.appendChild(statusMessageContainer);

      // show loading status
      form.append(newDiv);

      // disable the form until request is complete
      formInputSet.setAttribute('readonly', 'readonly');
      formInputDate.setAttribute('readonly', 'readonly');
      formInputSubmit.setAttribute('disabled', 'disabled');
      
      // fetch request to import cards
      fetch('/wp/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(new FormData(wpmtgImportSetForm))
      }).then(response => {
        return response.text();
      }).then(data => {
        newDiv.innerHTML = data;
        formInputSet.removeAttribute('readonly');
        formInputDate.removeAttribute('readonly');
        formInputSet.value = '';
        formInputSubmit.removeAttribute('disabled');
      })
    });
  }
});