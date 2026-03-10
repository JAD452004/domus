      // JavaScript pour gérer les interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion des clics sur les liens de navigation
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Gestion du bouton "Ajouter un bien"
            // const addPropertyBtns = document.querySelectorAll('.add-property-btn');
            // addPropertyBtns.forEach(btn => {
            //     btn.addEventListener('click', function() {
            //         alert('Fonctionnalité d\'ajout de bien à venir...');
            //     });
            // });
        });