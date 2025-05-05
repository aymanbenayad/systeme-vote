document.addEventListener('DOMContentLoaded', function() {
    checkUserSession();
});

function checkUserSession() {
    fetch('/backend/api/session.php?texte=IsConnected')
        .then(response => response.text())
        .then(data => {
            if (data === 'Yes') {
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
    fetch('/backend/api/session.php?texte=UserInfo')
        .then(response => response.json())
        .then(data => {
            document.getElementById('prenom').textContent = data.prenom;
            document.getElementById('info-prenom').textContent = data.prenom;
            document.getElementById('info-nom').textContent = data.nom;
            document.getElementById('info-email').textContent = data.email;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données utilisateur:');
        });
}

document.getElementById('logout')?.addEventListener('click', function() {
    fetch('/backend/api/session.php?texte=LogOut')
        .then(response => response.json())
        .then(data => {
            // Rediriger vers la page d'accueil
            window.location.href = 'index';
        })
        .catch(error => {
            console.error('Erreur lors de la déconnexion:');
            // Rediriger quand même en cas d'erreur
            window.location.href = 'index';
        });
});