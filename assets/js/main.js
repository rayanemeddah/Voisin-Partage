/* ============================================================
   VoisinPartage — JavaScript principal
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    // --------------------------------------------------------
    // 1. NAV MOBILE TOGGLE
    // --------------------------------------------------------
    const navToggle = document.getElementById('navToggle');
    const mainNav   = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', () => {
            mainNav.classList.toggle('open');
            const icon = navToggle.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-xmark');
        });
        // Fermer si clic en dehors
        document.addEventListener('click', (e) => {
            if (!navToggle.contains(e.target) && !mainNav.contains(e.target)) {
                mainNav.classList.remove('open');
            }
        });
    }

    // --------------------------------------------------------
    // 2. TOGGLE AFFICHAGE MOT DE PASSE
    // --------------------------------------------------------
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input    = document.getElementById(targetId);
            if (!input) return;
            const isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            const icon = btn.querySelector('i');
            icon.classList.toggle('fa-eye',        isText);
            icon.classList.toggle('fa-eye-slash', !isText);
        });
    });

    // --------------------------------------------------------
    // 3. PREVIEW IMAGE UPLOAD
    // --------------------------------------------------------
    const uploadInput   = document.getElementById('photo');
    const previewImage  = document.getElementById('previewImage');
    const uploadZone    = document.getElementById('uploadZone');

    if (uploadInput && previewImage) {
        uploadInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                showToast('Fichier non valide. Sélectionnez une image.', 'danger');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showToast('Fichier trop volumineux (max 5 Mo).', 'danger');
                return;
            }
            const reader = new FileReader();
            reader.onload = (ev) => {
                previewImage.src = ev.target.result;
                previewImage.style.display = 'block';
                const placeholder = uploadZone?.querySelector('.upload-placeholder');
                if (placeholder) placeholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    // Drag & drop sur la zone d'upload
    if (uploadZone) {
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = 'var(--primary)';
            uploadZone.style.background  = '#e8f5e9';
        });
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.style.borderColor = '';
            uploadZone.style.background  = '';
        });
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = '';
            uploadZone.style.background  = '';
            const files = e.dataTransfer.files;
            if (files.length > 0 && uploadInput) {
                // Transférer le fichier vers l'input
                const dt = new DataTransfer();
                dt.items.add(files[0]);
                uploadInput.files = dt.files;
                uploadInput.dispatchEvent(new Event('change'));
            }
        });
    }

    // --------------------------------------------------------
    // 4. CONFIRMATION SUPPRESSION
    // --------------------------------------------------------
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.dataset.confirm || 'Êtes-vous sûr ?')) {
                e.preventDefault();
            }
        });
    });

    // --------------------------------------------------------
    // 5. AUTO-DISMISS ALERTS après 5s
    // --------------------------------------------------------
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .5s ease';
            alert.style.opacity    = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // --------------------------------------------------------
    // 6. VALIDATION FORMULAIRES CÔTÉ CLIENT
    // --------------------------------------------------------

    // Inscription
    const inscriptionForm = document.querySelector('form[action="inscription.php"]');
    if (inscriptionForm) {
        inscriptionForm.addEventListener('submit', (e) => {
            const mdp    = document.getElementById('mot_de_passe')?.value || '';
            const confirm= document.getElementById('mdp_confirm')?.value  || '';
            const email  = document.getElementById('email')?.value        || '';

            if (mdp.length > 0 && mdp.length < 8) {
                e.preventDefault();
                showToast('Le mot de passe doit contenir au moins 8 caractères.', 'danger');
                return;
            }
            if (mdp && confirm && mdp !== confirm) {
                e.preventDefault();
                showToast('Les mots de passe ne correspondent pas.', 'danger');
                return;
            }
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                showToast('Adresse email invalide.', 'danger');
                return;
            }
        });
    }

    // --------------------------------------------------------
    // 7. COMPTEUR CARACTÈRES TEXTAREA
    // --------------------------------------------------------
    document.querySelectorAll('textarea[maxlength], textarea[data-maxlength]').forEach(ta => {
        const max = ta.maxLength || parseInt(ta.dataset.maxlength);
        if (!max || max < 0) return;
        const counter = document.createElement('small');
        counter.className = 'char-counter form-hint';
        ta.parentNode.insertBefore(counter, ta.nextSibling);
        const update = () => {
            const remaining = max - ta.value.length;
            counter.textContent = `${ta.value.length} / ${max} caractères`;
            counter.style.color = remaining < 20 ? 'var(--danger)' : '';
        };
        ta.addEventListener('input', update);
        update();
    });

    // --------------------------------------------------------
    // 8. SCROLL TO BOTTOM — CHAT
    // --------------------------------------------------------
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // --------------------------------------------------------
    // 9. SMOOTH SCROLL ANCHORS
    // --------------------------------------------------------
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', (e) => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // --------------------------------------------------------
    // 10. ACTIVE NAV LINK
    // --------------------------------------------------------
    const currentPath = window.location.pathname;
    document.querySelectorAll('.main-nav a').forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('?')[0])) {
            link.classList.add('active');
        }
    });

});

// ============================================================
// TOAST NOTIFICATION
// ============================================================
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed; top: 1rem; right: 1rem;
            z-index: 9999; display: flex;
            flex-direction: column; gap: .5rem;
            max-width: 340px;
        `;
        document.body.appendChild(container);
    }

    const colors = {
        success: { bg: '#e8f5e9', border: '#2e7d32', color: '#1b5e20' },
        danger:  { bg: '#ffebee', border: '#c62828', color: '#b71c1c' },
        warning: { bg: '#fff8e1', border: '#f9a825', color: '#e65100' },
        info:    { bg: '#e3f2fd', border: '#1565c0', color: '#0d47a1' },
    };
    const c = colors[type] || colors.info;

    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${c.bg};
        border: 1px solid ${c.border};
        color: ${c.color};
        border-radius: 8px;
        padding: .85rem 1.25rem;
        font-size: .9rem;
        box-shadow: 0 4px 16px rgba(0,0,0,.12);
        animation: slideIn .25s ease;
    `;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity .4s';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// Animation CSS
const style = document.createElement('style');
style.textContent = `
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);   opacity: 1; }
}
.char-counter { display: block; margin-top: .25rem; }
`;
document.head.appendChild(style);