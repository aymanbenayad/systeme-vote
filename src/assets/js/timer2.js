let adminStartTime = new Date('2025-04-17T12:00:00'); 
let currentTime; // Variable pour stocker l'heure actuelle

// Fonction pour récupérer l'heure du serveur en arrière-plan
async function getCurrentTime() {
    try {
        const response = await fetch('http://localhost:8000/backend/api/time.php'); // A remplacer par l'URL de ton backend
        const data = await response.json();
        currentTime = new Date(data.datetime); // Stocke l'heure actuelle du serveur
        console.log("Heure récupérée depuis l'API:", currentTime); // Log pour vérifier
    } catch (error) {
        console.error('Erreur API (backend) :', error);
        currentTime = new Date(); // Si l'API échoue, utilise l'heure locale
        console.log("Erreur lors de la récupération de l'heure depuis l'API, utilisation de l'heure locale:", currentTime);
    }
}

// Fonction pour afficher le temps restant
function updateTimer() {
    const miniTimerElement = document.getElementById('mini-timer');

    currentTime = new Date(); // Met à jour l'heure locale à chaque itération
    console.log("Heure actuelle (mise à jour):", currentTime); // Log de l'heure à chaque intervalle

    const timeRemaining = adminStartTime - currentTime;

    if (timeRemaining <= 0) {
        miniTimerElement.textContent = "Temps écoulé!";
        console.log("Le temps est écoulé."); // Log pour indiquer que le temps est écoulé
    } else {
        let days = Math.floor(timeRemaining / (3600 * 24 * 1000));
        let hours = Math.floor((timeRemaining % (3600 * 24 * 1000)) / (3600 * 1000));
        let minutes = Math.floor((timeRemaining % (3600 * 1000)) / (60 * 1000));

        let displayText = days > 0 ? `${days} jour${days > 1 ? 's' : ''}` : `${hours} h ${minutes} m`;
        
        miniTimerElement.textContent = displayText;
        console.log("Temps restant:", displayText); // Log pour afficher le temps restant à chaque itération
    }
}

// Fonction pour démarrer le mini-timer
async function startMiniTimer() {
    const miniTimerElement = document.getElementById('mini-timer');
    
    miniTimerElement.textContent = "Chargement...";  // Afficher immédiatement un texte de chargement
    
    await getCurrentTime();  // Récupère l'heure du serveur (ici on attend, mais tu peux aussi l'optimiser pour ne pas bloquer)
    
    updateTimer();  // Met à jour immédiatement dès le début
    
    // Met à jour toutes les minutes
    const interval = setInterval(function() {
        updateTimer();  // Met à jour le timer à chaque intervalle de 1 minute
    }, 60000); 
}

startMiniTimer();
