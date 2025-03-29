// Sélection des éléments
const hero = document.querySelector('.hero');
const signupBtn = document.getElementById('signup-btn');
const loginLink = document.getElementById('login-link');

// Fonction pour afficher le formulaire d'inscription
function showSignupForm() {
  hero.innerHTML = `
    <h2 class="form-title">Inscription</h2>
    <form id="signup-form">
      <div class="name-container">
        <input type="text" id="signup-nom" placeholder="Nom (optionnel)">
        <input type="text" id="signup-prenom" placeholder="Prénom" required>
      </div>
      <input type="email" placeholder="Email" required>
      <div class="password-container">
        <input type="password" id="signup-password" placeholder="Mot de passe" required>
        <img src="assets/img/oeilferme.png" alt="Afficher le mot de passe" class="password-toggle image-bold" id="toggle-signup-password">
      </div>
      <div class="password-container">
        <input type="password" id="signup-confirm-password" placeholder="Confirmer le mot de passe" required>
        <img src="assets/img/oeilferme.png" alt="Afficher le mot de passe" class="password-toggle image-bold" id="toggle-confirm-password">
      </div>
      <p id="password-error" class="error-message"></p>
      <p class="terms-text">
        En vous inscrivant, vous acceptez nos <a href="#" id="terms-link">règles d'utilisation</a>.
      </p>
      <button type="submit">S'inscrire</button>
    </form>
    <p class="switch-form">Déjà un compte ? <a href="#" id="switch-to-login">Se connecter</a></p>
  `;

  // Bascule vers le formulaire de connexion
  document.getElementById('switch-to-login').addEventListener('click', showLoginForm);

  // Mise à jour dynamique de la force du mot de passe
  document.getElementById('signup-password').addEventListener('input', function() {
    const password = this.value;
    const errorMessage = document.getElementById('password-error');
    const strength = zxcvbn(password);
    if (strength.score < 3) { // 0 ou 1 => mot de passe trop faible
      errorMessage.textContent = 'Mot de passe trop faible.';
    } else {
      errorMessage.textContent = '';
    }
  });

  // Validation de la confirmation de mot de passe et de la force du mot de passe
  document.getElementById('signup-form').addEventListener('submit', function(e) {
    const password = document.getElementById('signup-password').value;
    const confirmPassword = document.getElementById('signup-confirm-password').value;
    const errorMessage = document.getElementById('password-error');
    const strength = zxcvbn(password);

    if (password !== confirmPassword) {
      e.preventDefault();
      errorMessage.textContent = 'Les mots de passe ne correspondent pas.';
    } else if (strength.score < 3) {
      e.preventDefault();
      errorMessage.textContent = 'Mot de passe trop faible. Veuillez choisir un meilleur mot de passe.';
    } else {
      errorMessage.textContent = '';
    }
  });

  // Afficher/masquer le mot de passe principal
  document.getElementById('toggle-signup-password').addEventListener('click', function() {
    const passwordField = document.getElementById('signup-password');
    const toggleIcon = this;

    if (passwordField.type === 'password') {
      passwordField.type = 'text';
      toggleIcon.src = 'assets/img/oeilouvert.png';
    } else {
      passwordField.type = 'password';
      toggleIcon.src = 'assets/img/oeilferme.png';
    }
  });

  // Afficher/masquer le mot de passe de confirmation
  document.getElementById('toggle-confirm-password').addEventListener('click', function() {
    const confirmPasswordField = document.getElementById('signup-confirm-password');
    const toggleIcon = this;

    if (confirmPasswordField.type === 'password') {
      confirmPasswordField.type = 'text';
      toggleIcon.src = 'assets/img/oeilouvert.png';
    } else {
      confirmPasswordField.type = 'password';
      toggleIcon.src = 'assets/img/oeilferme.png';
    }
  });

  // Afficher la pop-up des règles d'utilisation
  document.getElementById('terms-link').addEventListener('click', function(e) {
    e.preventDefault();
    showTermsPopup();
  });
}

// Fonction pour afficher le formulaire de connexion
function showLoginForm() {
  hero.innerHTML = `
    <h2 class="form-title">Connexion</h2>
    <form id="login-form">
      <input type="email" placeholder="Email" required>
      <div class="password-container">
        <input type="password" id="login-password" placeholder="Mot de passe" required>
        <img src="assets/img/oeilferme.png" alt="Afficher le mot de passe" class="password-toggle image-bold" id="toggle-login-password">
      </div>
      <button type="submit">Se connecter</button>
    </form>
    <p class="switch-form">Pas de compte ? <a href="#" id="switch-to-signup">S'inscrire</a></p>
  `;

  // Bascule vers le formulaire d'inscription
  document.getElementById('switch-to-signup').addEventListener('click', showSignupForm);

  // Afficher/masquer le mot de passe
  document.getElementById('toggle-login-password').addEventListener('click', function() {
    const passwordField = document.getElementById('login-password');
    const toggleIcon = this;

    if (passwordField.type === 'password') {
      passwordField.type = 'text';
      toggleIcon.src = 'assets/img/oeilouvert.png';
    } else {
      passwordField.type = 'password';
      toggleIcon.src = 'assets/img/oeilferme.png';
    }
  });
}

// Fonction pour afficher la pop-up des règles d'utilisation
function showTermsPopup() {
  const popup = document.createElement('div');
  popup.className = 'terms-popup';
  popup.innerHTML = `
    <div class="popup-content">
      <h3>Règles d'utilisation</h3>
      <p>Bienvenue dans notre application. Avant de commencer, veuillez prendre connaissance des règles suivantes :</p>
      <button id="close-popup">Fermer</button>
    </div>
  `;
  document.body.appendChild(popup);

  // Fonction pour fermer la pop-up
  function closePopup() {
    popup.remove();
  }

  // Fermeture de la popup en cliquant sur le bouton
  document.getElementById('close-popup').addEventListener('click', closePopup);

  // Fermeture de la popup en cliquant en dehors de la fenêtre
  popup.addEventListener('click', function(e) {
    if (e.target === popup) {
      closePopup();
    }
  });
}

// Écouteurs d'événements pour les boutons
signupBtn.addEventListener('click', showSignupForm);
loginLink.addEventListener('click', (e) => {
  e.preventDefault();
  showLoginForm();
});
