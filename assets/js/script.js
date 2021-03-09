$(()=>{
    $(document).on('click','#regbtn',handleRegister);

    function handleRegister(event) {
        $.post('/api/register', $('form').serialize()).done(parseRegisterCallback);

        event.preventDefault();
        return false;
    }

    function parseRegisterCallback(response) {
        let errors = {
            'already_logged_in': 'Már be van jelentkezve!',
            'data_mismatch': 'Hibás adatok lettek elküldve.',
            'username_length': 'A felhasználónév nem megfelelő hosszúságú.',
            'email_length': 'Az email cím nem megfelelő hosszúságú.',
            'username_invalid_characters': 'A felhasználónév nem megfelelő karaktereket tartalmaz.',
            'fullname_invalid_characters': 'A teljes név nem megfelelő karaktereket tartalmaz.',
            'fullname_length': 'A teljes név nem megfelelő hosszúságú.',
            'password_length': 'A jelszó nem megfelelő hosszúságú.',
            'password_mismatch': 'A két jelszó nem egyezik.',
            'email_mismatch': 'A két email cím nem egyezik.',
            'invalid_email': 'Az email cím nem megfelelő formátumú.',
            'user_already_exists': 'A felhasználónév vagy email cím már foglalt.',
            'cookies_are_not_accepted': 'A sütik nincsenek engedélyezve.',
        };
        error = '';

        try {
            if(typeof response === 'string')
                response = JSON.parse(response);
        } catch(e) {
            registerError('Nem sikerült feldolgozni a regisztrációt.');
            console.log(e);
            return;
        }

        if(!response.success) return registerError(errors[response.error] || 'Adatbázishiba történt a regisztráció során.');

        window.location.href = '/register/verify';
    }
    
    function registerError(message) {
        $('#response').text(message);
        $('#response').addClass('failure');
    }
})