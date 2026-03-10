document.addEventListener("DOMContentLoaded", function () {
  console.log("Espace client DOMUS chargé avec succès");

  // Vérifie si l'utilisateur est bien connecté
  if (!document.querySelector(".user-info")) {
    console.warn("Utilisateur non connecté, redirection...");
    setTimeout(() => {
      window.location.href = "../CONNECTION/connexionUser.php";
    }, 1000);
  }

  // Initialise les fonctionnalités
  initMenuMobile();
  initFavoris();
  initSearchForm();
  initAnimations();

  // Effet de chargement initial
  document.body.style.opacity = "0";
  document.body.style.transition = "opacity 0.3s ease";

  setTimeout(() => {
    document.body.style.opacity = "1";
  }, 100);
});

// ============================
// GESTION DU MENU MOBILE
// ============================
function initMenuMobile() {
  const mobileToggle = document.getElementById("mobileMenuBtn");
  const navLinks = document.getElementById("navLinks");

  if (mobileToggle && navLinks) {
    mobileToggle.addEventListener("click", function (e) {
      e.stopPropagation();
      navLinks.classList.toggle("active");

      // Animation de l'icône burger/croix
      const icon = this.querySelector("i");
      if (navLinks.classList.contains("active")) {
        icon.classList.remove("fa-bars");
        icon.classList.add("fa-times");
        document.body.style.overflow = "hidden";
      } else {
        icon.classList.remove("fa-times");
        icon.classList.add("fa-bars");
        document.body.style.overflow = "";
      }
    });

    // Ferme le menu quand on clique en dehors
    document.addEventListener("click", function (event) {
      if (
        !mobileToggle.contains(event.target) &&
        !navLinks.contains(event.target)
      ) {
        navLinks.classList.remove("active");
        const icon = mobileToggle.querySelector("i");
        if (icon) {
          icon.classList.remove("fa-times");
          icon.classList.add("fa-bars");
        }
        document.body.style.overflow = "";
      }
    });

    // Ferme le menu quand on clique sur un lien
    const navItems = navLinks.querySelectorAll("a");
    navItems.forEach((item) => {
      item.addEventListener("click", function () {
        if (window.innerWidth <= 768) {
          navLinks.classList.remove("active");
          const icon = mobileToggle.querySelector("i");
          if (icon) {
            icon.classList.remove("fa-times");
            icon.classList.add("fa-bars");
          }
          document.body.style.overflow = "";
        }
      });
    });

    console.log("Menu mobile initialisé avec succès");
  }
}

// ============================
// GESTION DES FAVORIS
// ============================
function initFavoris() {
  loadFavoritesFromStorage();

  const favoriBtns = document.querySelectorAll(".favori-btn");
  favoriBtns.forEach((btn) => {
    const propertyId = btn.getAttribute("data-property-id");
    if (propertyId && isFavorite(propertyId)) {
      btn.classList.add("active");
      const icon = btn.querySelector("i");
      if (icon) {
        icon.style.color = "#ef4444";
      }
    }

    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      toggleFavori(propertyId, this);
    });
  });

  console.log("Système de favoris initialisé");
}

function toggleFavori(idMaison, btn) {
  if (!document.querySelector(".user-info")) {
    showNotification("Veuillez vous connecter pour ajouter aux favoris", "error");
    return;
  }

  const isActive = btn.classList.contains("active");
  const icon = btn.querySelector("i");

  if (!isActive) {
    btn.classList.add("active");
    if (icon) {
      icon.style.color = "#ef4444";
      icon.style.animation = "pulse 0.4s ease";
      setTimeout(() => { icon.style.animation = ""; }, 400);
    }
    saveFavoriteToStorage(idMaison, true);
    showNotification("Ajouté aux favoris", "success");
  } else {
    btn.classList.remove("active");
    if (icon) {
      icon.style.color = "#cbd5e1";
    }
    saveFavoriteToStorage(idMaison, false);
    showNotification("Retiré des favoris", "info");
  }

  sendFavoriteToServer(idMaison, !isActive);
}

function saveFavoriteToStorage(id, isFavorite) {
  try {
    let favorites = JSON.parse(localStorage.getItem("domus_favorites")) || [];
    if (isFavorite) {
      if (!favorites.includes(id)) favorites.push(id);
    } else {
      favorites = favorites.filter((favId) => favId !== id);
    }
    localStorage.setItem("domus_favorites", JSON.stringify(favorites));
  } catch (e) {
    console.warn("Impossible d'accéder au localStorage:", e);
  }
}

function loadFavoritesFromStorage() {
  try {
    return JSON.parse(localStorage.getItem("domus_favorites")) || [];
  } catch (e) {
    console.warn("Impossible de lire les favoris:", e);
    return [];
  }
}

function isFavorite(id) {
  const favorites = loadFavoritesFromStorage();
  return favorites.includes(id);
}

function sendFavoriteToServer(idMaison, isFavorite) {
  console.log(`Favori ${isFavorite ? "ajouté" : "retiré"} pour la propriété ${idMaison}`);
}

// ============================
// GESTION DU FORMULAIRE DE RECHERCHE
// ============================
function initSearchForm() {
  const searchForm = document.querySelector(".search-container form");
  if (!searchForm) return;

  // 1. Création et arrangement du style du bouton "Effacer"
  const clearBtn = document.createElement('button');
  clearBtn.type = 'button';
  clearBtn.className = 'clear-search-btn';
  clearBtn.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Réinitialiser';
  
  // Style en ligne pour s'assurer qu'il s'intègre parfaitement sans toucher au CSS
  clearBtn.style.cssText = `
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    height: 50px;
    padding: 0 20px;
    border-radius: 12px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
  `;

  // 2. Gestion des événements de survol (Hover)
  clearBtn.addEventListener("mouseenter", function () { 
    this.style.background = "#e2e8f0"; 
    this.style.color = "#0f172a"; 
  });
  clearBtn.addEventListener("mouseleave", function () { 
    this.style.background = "#f1f5f9"; 
    this.style.color = "#64748b"; 
  });

  // 3. Action de clic : REDIRECTION pour vider la recherche PHP
  clearBtn.addEventListener("click", function () { 
    this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; 
    // On recharge la page sans les paramètres ?search=...
    window.location.href = 'accueilClient.php'; 
  });

  // 4. Gestion de la soumission classique
  searchForm.addEventListener("submit", function (e) {
    const searchInput = this.querySelector('input[name="search"]');
    const typeSelect = this.querySelector('select[name="type"]');
    const villeSelect = this.querySelector('select[name="ville"]');

    if (!searchInput.value && !typeSelect.value && !villeSelect.value) {
      e.preventDefault();
      showNotification("Veuillez remplir au moins un critère de recherche.", "warning");
      return;
    }

    const submitBtn = this.querySelector(".search-btn");
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Recherche...';
    submitBtn.disabled = true;
  });

  // Insertion du bouton après le bouton "Rechercher"
  const searchBtn = searchForm.querySelector(".search-btn");
  if (searchBtn) {
    searchBtn.parentNode.insertBefore(clearBtn, searchBtn.nextSibling);
  }

  console.log("Formulaire de recherche arrangé");
}

// ============================
// ANIMATIONS
// ============================
function initAnimations() {
  const observerOptions = { threshold: 0.1, rootMargin: "0px 0px -50px 0px" };
  const observer = new IntersectionObserver(function (entries) {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "1";
        entry.target.style.transform = "translateY(0)";
      }
    });
  }, observerOptions);

  const cards = document.querySelectorAll(".maison-card");
  cards.forEach((card) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(20px)";
    card.style.transition = "opacity 0.5s ease, transform 0.5s ease";
    observer.observe(card);
  });

  const header = document.querySelector(".tete-content"); 
  if (header) header.style.animation = "fadeInUp 0.8s ease-out";
  
  const searchContainer = document.querySelector(".search-container"); 
  if (searchContainer) searchContainer.style.animation = "fadeInUp 0.8s ease-out 0.2s both";

  console.log("Animations initialisées");
}

// ============================
// NOTIFICATIONS
// ============================
function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  const icons = { success: "fa-check-circle", error: "fa-exclamation-circle", warning: "fa-exclamation-triangle", info: "fa-info-circle" };
  const icon = icons[type] || icons.info;
  
  notification.innerHTML = `
    <i class="fa-solid ${icon}"></i>
    <span>${message}</span>
    <button class="notification-close">&times;</button>
  `;
  
  notification.style.cssText = `position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px; color: white; z-index: 2000; display:flex; align-items:center; gap:12px; box-shadow:0 4px 12px rgba(0,0,0,0.15); min-width:300px; max-width:400px; transform:translateX(120%); opacity:0; transition: transform 0.3s ease, opacity 0.3s ease;`;
  
  const colors = { success: "#10b981", error: "#ef4444", warning: "#f59e0b", info: "#3b82f6" };
  notification.style.background = colors[type] || colors.info;
  
  const closeBtn = notification.querySelector('.notification-close');
  closeBtn.style.cssText = 'background:none;border:none;color:white;font-size:1.5rem;cursor:pointer;margin-left:auto;padding:0;width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:4px;';
  closeBtn.addEventListener('click', function(){ hideNotification(notification); });
  
  document.body.appendChild(notification);
  setTimeout(()=>{ notification.style.transform = 'translateX(0)'; notification.style.opacity = '1'; }, 10);
  
  const autoRemove = setTimeout(()=>hideNotification(notification), 5000);
  notification.addEventListener('mouseenter', ()=>clearTimeout(autoRemove));
}

function hideNotification(notification) {
  if (!notification || !notification.parentNode) return; 
  notification.style.transform = 'translateX(120%)'; 
  notification.style.opacity = '0'; 
  setTimeout(()=>{ if (notification.parentNode) notification.parentNode.removeChild(notification); }, 300);
}

window.toggleFavori = toggleFavori;
window.showNotification = showNotification;