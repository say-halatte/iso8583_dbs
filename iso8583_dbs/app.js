// Configuration de l'API
const API_BASE = 'api';

// Variables globales
let currentPage = 1;
let totalPages = 1;
let bearerToken = null;
let isAuthenticated = false;
const limit = 10;

// Initialiser l'application
document.addEventListener('DOMContentLoaded', function() {
    setupDragDrop();
    updateAuthUI();
});

// Fonctions d'authentification
function authenticate() {
    const token = document.getElementById('bearerToken').value.trim();
    if (!token) {
        showAlert('Veuillez saisir un token d\'authentification', 'error');
        return;
    }

    bearerToken = token;
    testAuthentication();
}

async function testAuthentication() {
    try {
        const response = await fetch(`${API_BASE}/`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${bearerToken}`,
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            isAuthenticated = true;
            showAlert('Authentification réussie !', 'success');
            updateAuthUI();
            loadMessages();
        } else {
            const error = await response.json();
            throw new Error(error.message || 'Authentification échouée');
        }
    } catch (error) {
        console.error('Auth error:', error);
        showAlert(`Erreur d'authentification: ${error.message}`, 'error');
        isAuthenticated = false;
        bearerToken = null;
        updateAuthUI();
    }
}

function disconnect() {
    isAuthenticated = false;
    bearerToken = null;
    document.getElementById('bearerToken').value = '';
    updateAuthUI();
    showAlert('Déconnexion effectuée', 'warning');
}

function updateAuthUI() {
    const authIndicator = document.getElementById('authIndicator');
    const authStatus = document.getElementById('authStatus');
    const authSection = document.getElementById('authSection');
    const uploadSection = document.getElementById('uploadSection');
    const messagesSection = document.getElementById('messagesSection');
    const disconnectBtn = document.getElementById('disconnectBtn');

    if (isAuthenticated) {
        authIndicator.classList.add('connected');
        authStatus.textContent = 'Authentifié';
        authSection.style.display = 'none';
        uploadSection.classList.remove('disabled');
        messagesSection.classList.remove('disabled');
        disconnectBtn.style.display = 'inline-block';
    } else {
        authIndicator.classList.remove('connected');
        authStatus.textContent = 'Non authentifié';
        authSection.style.display = 'block';
        uploadSection.classList.add('disabled');
        messagesSection.classList.add('disabled');
        disconnectBtn.style.display = 'none';
        
        // Effacer le conteneur de messages
        document.getElementById('messagesContainer').innerHTML = `
            <div class="loading">
                <p>🔒 Authentification requise pour accéder aux messages</p>
            </div>
        `;
    }
}

// Requête API avec authentification
async function authenticatedFetch(url, options = {}) {
    if (!isAuthenticated || !bearerToken) {
        throw new Error('Authentification requise');
    }

    const headers = {
        'Authorization': `Bearer ${bearerToken}`,
        'Content-Type': 'application/json',
        ...options.headers
    };

    console.log('Making request to:', url);
    console.log('With headers:', headers);

    try {
        const response = await fetch(url, { ...options, headers });
        return response;
    } catch (networkError) {
        console.error('Network error:', networkError);
        throw new Error(`Network error: ${networkError.message}`);
    }
}

// Configurer la fonctionnalité glisser-déposer
function setupDragDrop() {
    const uploadArea = document.querySelector('.upload-area');
    
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (isAuthenticated) uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (isAuthenticated && e.dataTransfer.files.length > 0) {
            uploadFile(e.dataTransfer.files[0]);
        }
    });
}

// Afficher le message d'alerte
function showAlert(message, type = 'info') {
    const alertsContainer = document.getElementById('alerts');
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    alert.textContent = message;
    alertsContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Télécharger le fichier XML
async function uploadFile(file) {
    if (!isAuthenticated) {
        showAlert('Authentification requise pour télécharger des fichiers', 'error');
        return;
    }

    if (!file) return;
    
    if (!file.name.toLowerCase().endsWith('.xml')) {
        showAlert('Veuillez sélectionner un fichier XML valide.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('xml_file', file);

    try {
        const response = await fetch(`${API_BASE}/`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${bearerToken}`
            },
            body: formData
        });

        const result = await response.json();

        if (response.ok) {
            showAlert(`Message ISO 8583 ajouté avec succès (ID: ${result.id}) - PAN chiffré automatiquement`, 'success');
            loadMessages();
        } else {
            throw new Error(result.message || 'Erreur lors du téléchargement');
        }
    } catch (error) {
        console.error('Upload error:', error);
        showAlert(`Erreur: ${error.message}`, 'error');
    }
}

// Charger les messages avec pagination
async function loadMessages(page = 1) {
    if (!isAuthenticated) return;

    currentPage = page;
    const container = document.getElementById('messagesContainer');
    
    container.innerHTML = `
        <div class="loading">
            <div class="spinner"></div>
            <p>Chargement des messages sécurisés...</p>
        </div>
    `;

    try {
        console.log('Fetching from URL:', `${API_BASE}/?page=${page}&limit=${limit}`);
        console.log('Using token:', bearerToken ? 'Token present' : 'No token');
        
        const response = await authenticatedFetch(`${API_BASE}/?page=${page}&limit=${limit}`);
        
        // Enregistrer les détails de la réponse pour le débogage
        console.log('Response status:', response.status);
        console.log('Response headers:', [...response.headers.entries()]);
        console.log('Response ok:', response.ok);
        
        // Obtenir d'abord le texte de réponse pour voir ce que nous avons réellement reçu
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Vérifier si la réponse est vide
        if (!responseText) {
            throw new Error('Empty response from server');
        }
        
        // Essayer d'analyser en JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            console.error('Response that failed to parse:', responseText);
            
            // Vérifier si la reponse ressemble à du HTML (cause courante due au mode error enable du serveur Apache)
            if (responseText.trim().startsWith('<')) {
                throw new Error('Server returned HTML instead of JSON. This usually means an authentication error or server issue.');
            } else {
                throw new Error(`Invalid JSON response: ${jsonError.message}. Response: ${responseText.substring(0, 100)}...`);
            }
        }

        if (response.ok) {
            displayMessages(data.data);
            updatePagination(data.pagination);
            updateStats(data.pagination);
        } else {
            throw new Error(data.message || `HTTP ${response.status}: ${data.error || 'Unknown error'}`);
        }
    } catch (error) {
        console.error('Load messages error:', error);
        
        // Gérer des types d'erreurs spécifiques
        if (error.message.includes('Unauthorized') || error.message.includes('401')) {
            isAuthenticated = false;
            bearerToken = null;
            updateAuthUI();
            showAlert('Session expirée. Veuillez vous reconnecter.', 'error');
        } else if (error.message.includes('NetworkError') || error.message.includes('fetch')) {
            showAlert('Erreur de connexion au serveur. Vérifiez votre connexion internet.', 'error');
        } else {
            showAlert(`Erreur: ${error.message}`, 'error');
        }
        
        container.innerHTML = `
            <div class="loading">
                <p>⚠️ Erreur: ${error.message}</p>
                <button onclick="loadMessages(${page})" style="margin-top: 10px;">Réessayer</button>
            </div>
        `;
    }
}

// Afficher les messages dans la grille
function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    
    if (messages.length === 0) {
        container.innerHTML = `
            <div class="loading">
                <p>🔭 Aucun message trouvé</p>
            </div>
        `;
        return;
    }

    const messagesGrid = document.createElement('div');
    messagesGrid.className = 'messages-grid';

    messages.forEach(message => {
        const messageCard = document.createElement('div');
        messageCard.className = 'message-card';
        messageCard.onclick = () => viewMessage(message.id);
        
        // Formater le montant avec la devise
        const formatAmount = (amount, currency) => {
            const amt = (amount / 100).toFixed(2);
            const currencySymbols = { '978': '€', '840': '$', '756': 'CHF' };
            return `${amt} ${currencySymbols[currency] || currency}`;
        };

        // Formater la date et l'heure
        const formatDateTime = (date, time) => {
            if (!date || !time) return 'N/A';
            const month = date.substring(0, 2);
            const day = date.substring(2, 4);
            const hour = time.substring(0, 2);
            const minute = time.substring(2, 4);
            const second = time.substring(4, 6);
            return `${day}/${month} ${hour}:${minute}:${second}`;
        };
        
        messageCard.innerHTML = `
            <div class="message-header">
                <span class="message-id">ID: ${message.id}</span>
                <span class="message-mti">MTI: ${message.mti}</span>
                <span class="message-amount">${formatAmount(message.amount, message.currency)}</span>
            </div>
            <div class="message-details">
                <div class="detail-item encrypted-field">
                    <div class="detail-label">PAN (Chiffré)</div>
                    <div class="detail-value pan-masked">${message.pan}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Code Traitement</div>
                    <div class="detail-value">${message.processing_code}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Date/Heure Transaction</div>
                    <div class="detail-value">${formatDateTime(message.transaction_date, message.transaction_time)}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">RRN</div>
                    <div class="detail-value">${message.rrn}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Terminal ID</div>
                    <div class="detail-value">${message.terminal_id}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Code Réponse</div>
                    <div class="detail-value">${message.response_code || 'N/A'}</div>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                <button class="btn" onclick="event.stopPropagation(); viewMessage(${message.id})" style="padding: 8px 16px; font-size: 0.9rem;">
                    👁️ Voir Détails
                </button>
                <button class="btn danger" onclick="event.stopPropagation(); deleteMessage(${message.id})" style="padding: 8px 16px; font-size: 0.9rem;">
                    🗑️ Supprimer
                </button>
            </div>
        `;
        messagesGrid.appendChild(messageCard);
    });

    container.innerHTML = '';
    container.appendChild(messagesGrid);
}

// Mettre à jour les contrôles de pagination
function updatePagination(pagination) {
    const paginationContainer = document.getElementById('pagination');
    totalPages = pagination.total_pages;
    
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    let paginationHTML = '';

    // Bouton précédent
    paginationHTML += `
        <button onclick="loadMessages(${pagination.page - 1})" ${pagination.page <= 1 ? 'disabled' : ''}>
            ⬅️ Précédent
        </button>
    `;

    // Numéros de page
    const startPage = Math.max(1, pagination.page - 2);
    const endPage = Math.min(totalPages, pagination.page + 2);

    if (startPage > 1) {
        paginationHTML += `<button onclick="loadMessages(1)">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<span>...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button onclick="loadMessages(${i})" ${i === pagination.page ? 'class="active"' : ''}>
                ${i}
            </button>
        `;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `<span>...</span>`;
        }
        paginationHTML += `<button onclick="loadMessages(${totalPages})">${totalPages}</button>`;
    }

    // Bouton Suivant
    paginationHTML += `
        <button onclick="loadMessages(${pagination.page + 1})" ${pagination.page >= totalPages ? 'disabled' : ''}>
            Suivant ➡️
        </button>
    `;

    paginationContainer.innerHTML = paginationHTML;
}

// Mettre à jour les statistiques
function updateStats(pagination) {
    document.getElementById('totalMessages').textContent = pagination.total;
    document.getElementById('currentPage').textContent = pagination.page;
}

// Afficher les détails du message dans la fenêtre modale
async function viewMessage(messageId) {
    const modal = document.getElementById('messageModal');
    const modalContent = document.getElementById('modalContent');
    
    modalContent.innerHTML = `
        <div class="loading">
            <div class="spinner"></div>
            <p>Chargement des détails sécurisés...</p>
        </div>
    `;
    
    modal.style.display = 'block';

    try {
        const response = await authenticatedFetch(`${API_BASE}/${messageId}`);
        const message = await response.json();

        if (response.ok) {
            displayMessageDetails(message);
        } else {
            throw new Error(message.message || 'Erreur lors du chargement');
        }
    } catch (error) {
        console.error('View message error:', error);
        modalContent.innerHTML = `
            <div class="loading">
                <p>⚠ Erreur: ${error.message}</p>
            </div>
        `;
    }
}

// Afficher les détails du message dans la fenêtre modale
function displayMessageDetails(message) {
    const modalContent = document.getElementById('modalContent');
    
    // Formater le montant avec la devise
    const formatAmount = (amount, currency) => {
        const amt = (amount / 100).toFixed(2);
        const currencySymbols = { '978': '€', '840': '$', '756': 'CHF' };
        return `${amt} ${currencySymbols[currency] || currency}`;
    };

    modalContent.innerHTML = `
        <h2 style="color: #2c3e50; margin-bottom: 30px;">🔍 Détails du Message ISO 8583</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="detail-item">
                <div class="detail-label">ID du Message</div>
                <div class="detail-value">${message.id}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">MTI (Message Type)</div>
                <div class="detail-value">${message.mti}</div>
            </div>
            <div class="detail-item encrypted-field">
                <div class="detail-label">PAN (Chiffré)</div>
                <div class="detail-value pan-masked" id="panDisplay">${message.pan}</div>
                <button class="toggle-pan" onclick="togglePan('${message.id}')">👁️ Révéler</button>
            </div>
            <div class="detail-item">
                <div class="detail-label">Code de Traitement</div>
                <div class="detail-value">${message.processing_code}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Montant</div>
                <div class="detail-value">${formatAmount(message.amount, message.currency)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Heure Transaction</div>
                <div class="detail-value">${message.transaction_time}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Date Transaction</div>
                <div class="detail-value">${message.transaction_date}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">RRN</div>
                <div class="detail-value">${message.rrn}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Code Réponse</div>
                <div class="detail-value">${message.response_code || 'N/A'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Terminal ID</div>
                <div class="detail-value">${message.terminal_id}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Devise</div>
                <div class="detail-value">${message.currency}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Ajouté en BD</div>
                <div class="detail-value">${new Date(message.created_at).toLocaleString('fr-FR')}</div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <button class="btn danger" onclick="deleteMessage(${message.id}); closeModal();">
                🗑️ Supprimer ce Message
            </button>
        </div>
    `;
    
    // Stockez le PAN complet pour la fonctionnalité de basculement
    window.messageFullPan = message.pan_full;
    window.messageMaskedPan = message.pan;
}

// Basculer l'affichage PAN
function togglePan(messageId) {
    const panDisplay = document.getElementById('panDisplay');
    const toggleBtn = panDisplay.parentNode.querySelector('.toggle-pan');
    
    if (panDisplay.textContent === window.messageMaskedPan) {
        panDisplay.textContent = window.messageFullPan;
        panDisplay.style.color = '#27ae60';
        toggleBtn.textContent = '🙈 Masquer';
    } else {
        panDisplay.textContent = window.messageMaskedPan;
        panDisplay.style.color = '#e74c3c';
        toggleBtn.textContent = '👁️ Révéler';
    }
}

// Supprimer le message
async function deleteMessage(messageId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.')) {
        return;
    }

    try {
        const response = await authenticatedFetch(`${API_BASE}/${messageId}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Message supprimé avec succès', 'success');
            loadMessages(currentPage);
        } else {
            throw new Error(result.message || 'Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Delete message error:', error);
        showAlert(`Erreur: ${error.message}`, 'error');
    }
}

// Fermer la fenêtre modale
function closeModal() {
    document.getElementById('messageModal').style.display = 'none';
}

// Écouteurs d'événements
document.addEventListener('DOMContentLoaded', function() {
    // Fermer la fenêtre modale en cliquant à l'extérieur
    window.onclick = function(event) {
        const modal = document.getElementById('messageModal');
        if (event.target === modal) {
            closeModal();
        }
    };

    // Manipuler la touche Échap pour fermer la fenêtre modale
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    // Gérer la touche Entrée dans la saisie du jeton
    const bearerTokenInput = document.getElementById('bearerToken');
    if (bearerTokenInput) {
        bearerTokenInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                authenticate();
            }
        });
    }
});

