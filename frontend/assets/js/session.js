// session.js

async function checkSession() {
    try {
        // Faire la requête vers session.php
        const response = await fetch('/backend/api/session.php', {
            method: 'GET',
            credentials: 'include' // Important pour envoyer les cookies
        });

        // Vérifier si la réponse est OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Convertir la réponse en JSON
        const data = await response.json();

        // Afficher la réponse dans la console
        console.log('Réponse du serveur:', data);

        // Traiter la réponse en fonction du statut
        if (data.status === 'success') {
            console.log('Utilisateur connecté !');
            console.log('Informations utilisateur:', data.user_data);
            
            // Afficher chaque propriété individuellement
            console.log('ID:', data.user_data.user_id);
            console.log('Email:', data.user_data.email);
            console.log('Nom:', data.user_data.nom);
            console.log('Prénom:', data.user_data.prenom);
            console.log('Date d\'inscription:', data.user_data.sign_date);
            console.log('Fingerprint:', data.user_data.fingerprint);
            console.log('IP d\'inscription:', data.user_data.sign_ip);
            console.log('ID d\'inscription:', data.user_data.sign_id);
            
            // Vous pouvez également appeler d'autres fonctions ici
            // Par exemple: updateUI(data.user_data);
            
        } else {
            console.log('Erreur:', data.message);
            
            // En cas d'erreur, vous pouvez rediriger vers la page de login
            // Par exemple: window.location.href = 'login.php';
        }

    } catch (error) {
        console.error('Erreur lors de la requête:', error);
    }
}

// Exécuter la fonction au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
});

// Vous pouvez aussi exposer la fonction pour l'appeler manuellement
window.checkSession = checkSession;