const form = document.getElementById('ecoContactForm');
const responseDiv = document.getElementById('eco-form-response');
const submitButton = form.querySelector('button[type="submit"]');

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  // Changer l'apparence de la souris et désactiver le bouton
  document.body.style.cursor = 'wait';
  submitButton.disabled = true;
  submitButton.textContent = 'Envoi en cours...';
  responseDiv.textContent = '';

  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  try {
    const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/send-mail.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    });

    const result = await response.data;
    responseDiv.textContent = result.message;
  } catch (err) {
    responseDiv.textContent = "Une erreur est survenue. Veuillez réessayer.";
  } finally {
    // Remettre la souris et le bouton à l'état normal
    document.body.style.cursor = 'default';
    submitButton.disabled = false;
    submitButton.textContent = 'Envoyer';
  }
});
