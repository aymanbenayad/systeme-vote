const hero = document.querySelector('.hero');
const signupBtn = document.getElementById('signup-btn');
const loginLink = document.getElementById('login-link');

function showSignupForm() {
  hero.innerHTML = `
    <h2 class="form-title">Inscription</h2>
<form id="signup-form" action="backend/api/signup.php" method="POST">
  <div class="names-container">
    <input type="text" id="signup-nom" name="nom" placeholder="Nom (optionnel)">
    <input type="text" id="signup-prenom" name="prenom" placeholder="Prénom" required>
  </div>
  <input type="email" id="signup-email" name="email" placeholder="Email" required>
  <div class="password-container">
    <input type="password" id="signup-password" name="password" placeholder="Mot de passe" required>
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
  
  FingerprintJS.load().then(fp => {
    fp.get().then(result => {
      const visitorId = result.visitorId;
      fingerprint = visitorId;
    }).catch(error => {
      console.error(error);
    });
  }).catch(error => {
    console.error(error);
  });
  
  document.getElementById('switch-to-login').addEventListener('click', showLoginForm);
  
  document.getElementById('signup-password').addEventListener('input', function () {
    const password = this.value;
    const errorMessage = document.getElementById('password-error');
    const strength = zxcvbn(password);
    if (strength.score < 2) {
      errorMessage.textContent = 'Mot de passe trop faible.';
    } else {
      errorMessage.textContent = '';
    }
  });
  
  document.getElementById('signup-form').addEventListener('submit', function (e) {
    e.preventDefault();
    
    const nom = document.getElementById('signup-nom').value;
    const prenom = document.getElementById('signup-prenom').value;
    const email = document.getElementById('signup-email').value;
    const password = document.getElementById('signup-password').value;
    const confirmPassword = document.getElementById('signup-confirm-password').value;
    const errorMessage = document.getElementById('password-error');
    
    const strength = zxcvbn(password);
    let isValid = true;
    
    if (password !== confirmPassword) {
      errorMessage.textContent = 'Les mots de passe ne correspondent pas.';
      isValid = false;
    } 
    else if (strength.score < 2) {
      errorMessage.textContent = 'Mot de passe trop faible. Veuillez choisir un meilleur mot de passe.';
      isValid = false;
    } 
    else {
      errorMessage.textContent = '';
    }
    
    if (isValid) {
      fetch("backend/api/signup.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `nom=${encodeURIComponent(nom)}&prenom=${encodeURIComponent(prenom)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&fingerprint=${encodeURIComponent(fingerprint)}`
      })
      .then(response => response.text())
      .then(data => {
        if (data === "VALID_FOR_NEXT_STEP") {
          showVerificationStep(email);
        } else {
          errorMessage.textContent = data; // sinon afficher l'erreur ou le message classique
        }
      })
           
      .catch(error => console.error("Erreur :", error));
    }
  });
  
  document.getElementById('toggle-signup-password').addEventListener('click', function () {
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

  document.getElementById('toggle-confirm-password').addEventListener('click', function () {
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

  document.getElementById('terms-link').addEventListener('click', function (e) {
    e.preventDefault();
    showTermsPopup();
  });
}

function showLoginForm() {
  hero.innerHTML = `
    <h2 class="form-title">Connexion</h2>
    <form id="login-form">
      <input type="email" id="login-email" placeholder="Email" required>
      <div class="password-container">
        <input type="password" id="login-password" placeholder="Mot de passe" required>
        <img src="assets/img/oeilferme.png" alt="Afficher le mot de passe" class="password-toggle image-bold" id="toggle-login-password">
      </div>
      <p id="password-error" class="error-message"></p>
      <button type="submit">Se connecter</button>
    </form>
    <p class="switch-form">Pas de compte ? <a href="#" id="switch-to-signup">S'inscrire</a></p>
  `;

  document.getElementById('switch-to-signup').addEventListener('click', showSignupForm);

  document.getElementById('toggle-login-password').addEventListener('click', function () {
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
  
  document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    const errorMessage = document.getElementById('password-error');
    
    fetch("backend/api/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
    })
    .then(response => response.text())
    .then(data => {
      errorMessage.textContent = data;
    })    
    .catch(error => console.error("Erreur :", error));
  });
}

function showTermsPopup() {
  const popup = document.createElement('div');
  popup.className = 'terms-popup';
  popup.innerHTML = `     
<div class="popup-content">
  <h3>Règles d'utilisation</h3>
  <p>Bienvenue dans notre application. Avant de continuer, veuillez lire les règles suivantes :</p>
  
  <p><strong>1. Acceptation des conditions</strong></p>
  <p>En utilisant cette application, vous acceptez ces règles. Si vous n'êtes pas d'accord, veuillez ne pas utiliser le service.</p>
  
  <p><strong>2. Utilisation autorisée</strong></p>
  <p>Vous vous engagez à ne pas utiliser l'application à des fins illégales, frauduleuses ou nuisibles.</p>
  <p>Tout manquement à ces règles peut entraîner la suspension ou la suppression de votre compte.</p>
  
  <p><strong>3. Sécurité et confidentialité</strong></p>
  <p>Nous générons une empreinte unique (fingerprint) pour chaque utilisateur afin d'assurer la sécurité du système et l'authentification unique.</p>
  <p>Cette empreinte est stockée de manière chiffrée (AES) et utilisée uniquement pour prévenir les fraudes.</p>
  <p>Vos mots de passe sont stockés sous forme hachée pour garantir leur protection.</p>
  <p>Vos données personnelles ne sont ni revendues ni partagées avec des tiers sans votre consentement.</p>
  
  <p><strong>4. Conservation et suppression des données</strong></p>
  <p>Les données collectées sont conservées pour une durée maximale de 6 mois, sauf obligation légale contraire.</p>
  <p>Vous avez le droit de demander la suppression de vos informations en nous contactant à <a href="mailto:ecovision.vote@gmail.com">ecovision.vote@gmail.com</a>.</p>
  
  <p><strong>5. Respect des autres utilisateurs</strong></p>
  <p>Tout comportement abusif, contenu offensant ou non conforme peut entraîner des sanctions.</p>
  <p>Nous nous réservons le droit de suspendre ou supprimer un compte en cas de non-respect des règles.</p>
  
  <p><strong>6. Modification des règles</strong></p>
  <p>Ces règles peuvent être mises à jour à tout moment.</p>
  
  <button id="close-popup">Fermer</button>
</div>
`;
  document.body.appendChild(popup);

  function closePopup() {
    popup.remove();
  }

  document.getElementById('close-popup').addEventListener('click', closePopup);

  popup.addEventListener('click', function (e) {
    if (e.target === popup) {
      closePopup();
    }
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closePopup();
    }
  });
}

signupBtn.addEventListener('click', showSignupForm);
loginLink.addEventListener('click', (e) => {
  e.preventDefault();
  showLoginForm();
});

function showVerificationStep(email) {
  hero.innerHTML = `
    <h2 class="form-title">Vérification</h2>
    <form id="verify-code-form">
      <p class="info-text">Un code de vérification a été envoyé à <strong>${email}</strong>.</p>
      <input type="text" id="verification-code" pattern="^[0-9]{6}$" placeholder="Code de vérification" required 
       title="6 chiffres">

      <p id="verification-error" class="error-message"></p>

      <button type="submit">Vérifier</button>
      <br>
    </form>
    <p class="terms-text">
      Pas reçu le code ? <a href="#" id="resend-code">Renvoyer</a>
    </p>
  `;

  document.getElementById('verify-code-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const code = document.getElementById('verification-code').value;
    verifyCode(code); // À implémenter côté JS
  });

  document.getElementById('resend-code').addEventListener('click', function (e) {
    e.preventDefault();
    resendVerificationCode(email); // À implémenter aussi
  });
}
