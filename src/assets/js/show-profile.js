document.addEventListener('DOMContentLoaded', function() {
    checkUserSession();
});

function checkUserSession() {
    fetch('https://systeme-vote-backend-production.up.railway.app/api/session.php?texte=IsConnected')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.message === true) {
                document.getElementById('login-required').style.display = 'none';
                document.getElementById('profil-authenticated').style.display = 'block';

                loadUserData();
            } else {
                document.getElementById('login-required').style.display = 'block';
                document.getElementById('profil-authenticated').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification de la session:');
            document.getElementById('login-required').style.display = 'block';
            document.getElementById('profil-authenticated').style.display = 'none';
        });

}

function loadUserData() {
    fetch('https://systeme-vote-backend-production.up.railway.app/api/session.php?texte=UserInfo')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('prenom').textContent = data.prenom;
                document.getElementById('info-prenom').textContent = data.prenom;
                document.getElementById('info-nom').textContent = data.nom;
                document.getElementById('info-email').textContent = data.email;
            } else {
                console.error('Erreur lors du chargement des données utilisateur.');
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données utilisateur:');
        });

}

document.getElementById('logout')?.addEventListener('click', function() {
    fetch('https://systeme-vote-backend-production.up.railway.app/api/session.php?texte=LogOut')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = 'index';
            } else {
                console.error('Erreur lors de la déconnexion.');
                window.location.href = 'index';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la déconnexion:');
            window.location.href = 'index';
        });

});