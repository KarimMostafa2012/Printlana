document.addEventListener("DOMContentLoaded", function () {
    const languageLinks = document.querySelectorAll('.custom-language-switcher a.wpml-ls-link');

    languageLinks.forEach(link => {
        const icon = document.createElement('span');
        icon.className = 'language-icon';
        icon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32" style="margin-right:8px;vertical-align:middle;">
            <path d="M14.8393 20.0934L18.226 16.7467L18.186 16.7067C15.9265 14.1967 14.2387 11.226 13.2393 8.00002H9.33268V5.33335H18.666V2.66669H21.3327V5.33335H30.666V8.00002H15.7727C16.666 10.56 18.0793 13 19.9993 15.1334C21.2393 13.76 22.266 12.2534 23.0793 10.6667H25.746C24.7727 12.84 23.4393 14.8934 21.7727 16.7467L28.5593 23.44L26.666 25.3334L19.9993 18.6667L15.8527 22.8134L14.8393 20.0934ZM7.33268 13.3334H9.99935L15.9993 29.3334H13.3327L11.8393 25.3334H5.50601L3.99935 29.3334H1.33268L7.33268 13.3334ZM10.826 22.6667L8.66602 16.8934L6.50601 22.6667H10.826Z" fill="#0044F1"/>
        </svg>`;

        link.insertBefore(icon, link.firstChild);
    });
});