//Loads VK cookie into gravity hidden input. In case there is no cookie, sets value to empty.

let gravityHiddenInput = document.querySelectorAll( '.gfield.clientify_cookie input.gform_hidden' );
if(gravityHiddenInput !== 'undefined'){
    let vkcookie = forms_clientify_getCookie('vk');
    for (let i = 0; i < gravityHiddenInput.length; i++) {
        gravityHiddenInput[i].value = vkcookie;
    }
}

let contactformHiddenInput = document.querySelectorAll( 'input.wpcf7-form-control.clientify_cookie' );
if(contactformHiddenInput !== 'undefined'){
    let vkcookie = forms_clientify_getCookie('vk');
    for (let i = 0; i < contactformHiddenInput.length; i++) {
        contactformHiddenInput[i].value = vkcookie;
    }
}

function forms_clientify_getCookie(cname) {
    if(document.cookie !== 'undefined') {
        let name = cname + "=";
        let decodedCookie = decodeURIComponent(document.cookie);
        let ca = decodedCookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
    }
    return "";
}