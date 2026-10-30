document.addEventListener('DOMContentLoaded', function() {
    const chatbotWidget = document.getElementById('chatbotWidget');
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotContainer = document.getElementById('chatbotContainer');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSend = document.getElementById('chatbotSend');

    if (!chatbotWidget) return;

    // Toggle chatbot
    chatbotToggle.addEventListener('click', function() {
        chatbotContainer.classList.toggle('active');
        chatbotToggle.classList.toggle('active');
    });

    chatbotClose.addEventListener('click', function() {
        chatbotContainer.classList.remove('active');
        chatbotToggle.classList.remove('active');
    });

    // Send message
    function sendMessage() {
        const message = chatbotInput.value.trim();
        if (!message) return;

        // Add user message
        addMessage(message, 'user');
        chatbotInput.value = '';

        // Process message
        setTimeout(() => {
            processMessage(message);
        }, 500);
    }

    chatbotSend.addEventListener('click', sendMessage);
    chatbotInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });

    function addMessage(text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        
        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.innerHTML = sender === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
        
        const content = document.createElement('div');
        content.className = 'message-content';
        content.innerHTML = `<p>${text}</p>`;
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(content);
        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    function processMessage(message) {
        const lowerMessage = message.toLowerCase();
        
        if (lowerMessage.includes('analyse') || lowerMessage.includes('recommandation')) {
            analyzeProfile();
        } else if (lowerMessage.includes('offre') || lowerMessage.includes('stage')) {
            recommendOffers();
        } else if (lowerMessage.includes('événement') || lowerMessage.includes('event')) {
            recommendEvents();
        } else if (lowerMessage.includes('badge') || lowerMessage.includes('compétence')) {
            showSkills();
        } else if (lowerMessage.includes('oui') || lowerMessage.includes('ok') || lowerMessage.includes('daccord')) {
            analyzeProfile();
        } else {
            addMessage("Je peux vous aider avec :<br>• Analyse de votre profil<br>• Recommandations d'offres<br>• Recommandations d'événements<br>• Vos badges et compétences<br><br>Que souhaitez-vous faire ?", 'bot');
        }
    }

    function analyzeProfile() {
        const badges = window.studentData?.badges || ['Débutant'];
        const skills = window.studentData?.skills || 'Informatique';
        
        let response = `<strong>Analyse de votre profil :</strong><br><br>`;
        response += `🏆 <strong>Badges :</strong> ${Array.isArray(badges) ? badges.join(', ') : badges}<br>`;
        response += `💡 <strong>Compétences :</strong> ${skills}<br><br>`;
        response += `Sur cette base, je peux vous recommander les meilleures offres et événements. Voulez-vous voir les recommandations ?`;
        
        addMessage(response, 'bot');
    }

    function recommendOffers() {
        const offers = window.studentData?.offers || [];
        
        if (offers.length === 0) {
            addMessage("Aucune offre disponible pour le moment.", 'bot');
            return;
        }
        
        let response = `<strong>Meilleures offres pour vous :</strong><br><br>`;
        offers.slice(0, 3).forEach((offer, index) => {
            response += `${index + 1}. <strong>${offer.titre}</strong><br>`;
            response += `   📍 ${offer.nomsociete} - ${offer.lieu || 'Tunis'}<br>`;
            response += `   📅 ${offer.type || 'Stage'}<br><br>`;
        });
        
        addMessage(response, 'bot');
    }

    function recommendEvents() {
        const events = window.studentData?.events || [];
        
        if (events.length === 0) {
            addMessage("Aucun événement disponible pour le moment.", 'bot');
            return;
        }
        
        let response = `<strong>Événements recommandés :</strong><br><br>`;
        events.slice(0, 3).forEach((event, index) => {
            response += `${index + 1}. <strong>${event.titre}</strong><br>`;
            response += `   📅 ${new Date(event.dateEvent).toLocaleDateString('fr-FR')}<br>`;
            response += `   📍 ${event.lieu || 'ESEN'}<br><br>`;
        });
        
        addMessage(response, 'bot');
    }

    function showSkills() {
        const badges = window.studentData?.badges || ['Débutant'];
        const skills = window.studentData?.skills || 'Informatique';
        
        let response = `<strong>Vos badges et compétences :</strong><br><br>`;
        response += `🏆 <strong>Badges obtenus :</strong><br>`;
        if (Array.isArray(badges)) {
            badges.forEach(badge => {
                response += `   • ${badge}<br>`;
            });
        } else {
            response += `   • ${badges}<br>`;
        }
        response += `<br>💡 <strong>Compétence principale :</strong> ${skills}<br><br>`;
        response += `Continuez à développer vos compétences pour débloquer plus de badges !`;
        
        addMessage(response, 'bot');
    }
});
