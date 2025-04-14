let adminStartTime = new Date('2025-04-17T12:00:00'); 
let currentTime; // Variable pour stocker l'heure actuelle

async function getCurrentTime() {
    try {
        const response = await fetch('http://localhost:8000/backend/api/time.php'); // A remplacer
        const data = await response.json();
        currentTime = new Date(data.datetime); // Stocke l'heure actuelle
    } catch (error) {
        console.error('Erreur API (backend) :', error);
        currentTime = new Date(); // Utilise l'heure locale si l'API échoue
    }
}

async function startTimer() {
    const timerElement = document.getElementById('timer');
    
    timerElement.textContent = "Chargement...";
    await getCurrentTime(); // Récupère l'heure du serveur au début

    const interval = setInterval(function() {
        // On met à jour l'heure à chaque seconde
        currentTime = new Date(); // Met à jour l'heure à chaque itération

        const timeRemaining = adminStartTime - currentTime;

        if (timeRemaining <= 0) {
            clearInterval(interval);
            timerElement.textContent = "Temps écoulé!";
        } else {
            let days = Math.floor(timeRemaining / (3600 * 24 * 1000));
            let hours = Math.floor((timeRemaining % (3600 * 24 * 1000)) / (3600 * 1000));
            let minutes = Math.floor((timeRemaining % (3600 * 1000)) / (60 * 1000));
            let seconds = Math.floor((timeRemaining % (60 * 1000)) / 1000);
            
            timerElement.textContent = `${days} d ${hours} h ${minutes} m ${seconds} s`;
        }
    }, 1000);
}

startTimer();
