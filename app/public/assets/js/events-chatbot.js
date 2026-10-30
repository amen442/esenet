/**
 * ESEN Connect - Events Chatbot
 * Chatbot spécialisé pour les événements ESEN
 */

class EventsChatbot {
    constructor() {
        this.apiEndpoint = '/api/events/chatbot';
        this.eventDatabase = null;
        this.userPreferences = null;
        this.currentContext = null;
        this.conversationHistory = [];
        
        this.initializeChatbot();
    }

    /**
     * Initialise le chatbot
     */
    async initializeChatbot() {
        await this.loadEventDatabase();
        await this.loadUserPreferences();
        this.setupEventHandlers();
        console.log('🎭 Events Chatbot initialized');
    }

    /**
     * Traite un message utilisateur
     */
    async processMessage(message, context = {}) {
        try {
            // Analyse du message
            const analysis = this.analyzeMessage(message, context);
            
            // Génération de la réponse
            const response = await this.generateResponse(analysis);
            
            // Mise à jour du contexte
            this.updateContext(analysis, response);
            
            return {
                response: response,
                context: this.currentContext,
                suggestions: this.generateSuggestions(analysis),
                relatedEvents: this.findRelatedEvents(analysis)
            };
            
        } catch (error) {
            console.error('Error processing message:', error);
            return this.generateFallbackResponse(message);
        }
    }

    /**
     * Analyse le message utilisateur
     */
    analyzeMessage(message, context) {
        const text = message.toLowerCase().trim();
        
        return {
            original: message,
            intent: this.detectIntent(text),
            entities: this.extractEntities(text),
            timeframe: this.extractTimeframe(text),
            category: this.extractCategory(text),
            location: this.extractLocation(text),
            sentiment: this.analyzeSentiment(text),
            urgency: this.assessUrgency(text),
            context: context
        };
    }

    /**
     * Détecte l'intention du message
     */
    detectIntent(text) {
        const intents = {
            search_events: {
                patterns: ['recherche', 'trouve', 'cherche', 'montre', 'événement', 'activité', 'quoi', 'quand'],
                confidence: 0
            },
            event_details: {
                patterns: ['détails', 'information', 'plus sur', 'en savoir plus', 'décris'],
                confidence: 0
            },
            registration: {
                patterns: ['inscrire', 'participer', 's\'inscrire', 'réserver', 'inscription'],
                confidence: 0
            },
            calendar: {
                patterns: ['calendrier', 'agenda', 'planning', 'dates', 'horaire'],
                confidence: 0
            },
            recommendations: {
                patterns: ['recommande', 'suggère', 'conseil', 'me conseille'],
                confidence: 0
            },
            help: {
                patterns: ['aide', 'help', 'support', 'comment', 'comment faire'],
                confidence: 0
            },
            general_chat: {
                patterns: ['salut', 'bonjour', 'merci', 'au revoir', 'ça va'],
                confidence: 0
            }
        };

        // Calcul de la confiance pour chaque intention
        for (const [intent, data] of Object.entries(intents)) {
            const matches = data.patterns.filter(pattern => text.includes(pattern)).length;
            data.confidence = matches / data.patterns.length;
        }

        // Retourne l'intention avec la plus haute confiance
        const bestIntent = Object.entries(intents)
            .reduce((best, [intent, data]) => 
                data.confidence > best.confidence ? { intent, confidence: data.confidence } : best,
                { intent: 'general_chat', confidence: 0 }
            );

        return bestIntent;
    }

    /**
     * Extrait les entités
     */
    extractEntities(text) {
        const entities = {
            event_types: [],
            locations: [],
            dates: [],
            keywords: [],
            numbers: []
        };

        // Types d'événements
        const eventTypes = ['conférence', 'atelier', 'workshop', 'séminaire', 'réunion', 'formation', 'networking'];
        entities.event_types = eventTypes.filter(type => text.includes(type));

        // Lieux
        const locations = ['esen', 'campus', 'tunis', 'salle', 'amphi', 'en ligne', 'online'];
        entities.locations = locations.filter(location => text.includes(location));

        // Extraction des nombres
        const numbers = text.match(/\b\d+\b/g);
        entities.numbers = numbers ? numbers.map(Number) : [];

        // Mots-clés
        const keywords = text.split(/\s+/).filter(word => word.length > 3);
        entities.keywords = keywords.slice(0, 10);

        return entities;
    }

    /**
     * Extrait la période temporelle
     */
    extractTimeframe(text) {
        const timeframes = {
            today: ['aujourd\'hui', 'today'],
            tomorrow: ['demain', 'tomorrow'],
            this_week: ['cette semaine', 'this week'],
            next_week: ['semaine prochaine', 'next week'],
            this_month: ['ce mois', 'this month'],
            upcoming: ['prochain', 'futur', 'à venir', 'bientôt'],
            past: ['passé', 'précédent', 'déjà passé']
        };

        for (const [timeframe, keywords] of Object.entries(timeframes)) {
            if (keywords.some(keyword => text.includes(keyword))) {
                return timeframe;
            }
        }

        return 'any';
    }

    /**
     * Extrait la catégorie
     */
    extractCategory(text) {
        const categories = {
            technical: ['programmation', 'code', 'développement', 'tech', 'informatique'],
            business: ['business', 'entreprise', 'marketing', 'finance', 'management'],
            creative: ['design', 'créativité', 'art', 'multimédia'],
            academic: ['étude', 'recherche', 'formation', 'pédagogie'],
            social: ['social', 'réseau', 'rencontre', 'team building']
        };

        for (const [category, keywords] of Object.entries(categories)) {
            if (keywords.some(keyword => text.includes(keyword))) {
                return category;
            }
        }

        return 'general';
    }

    /**
     * Extrait la localisation
     */
    extractLocation(text) {
        const locations = {
            online: ['en ligne', 'online', 'virtuel', 'digital', 'zoom', 'teams'],
            campus: ['campus', 'esen', 'école', 'physique', 'présentiel'],
            external: ['extérieur', 'hors', 'autre lieu', 'downtown', 'centre-ville']
        };

        for (const [location, keywords] of Object.entries(locations)) {
            if (keywords.some(keyword => text.includes(keyword))) {
                return location;
            }
        }

        return 'any';
    }

    /**
     * Analyse le sentiment
     */
    analyzeSentiment(text) {
        const positive = ['bon', 'excellent', 'super', 'génial', 'intéressant', 'merci', 'content'];
        const negative = ['mauvais', 'problème', 'difficile', 'compliqué', 'déçu', 'ennuyeux'];
        
        const positiveCount = positive.filter(word => text.includes(word)).length;
        const negativeCount = negative.filter(word => text.includes(word)).length;
        
        if (positiveCount > negativeCount) return 'positive';
        if (negativeCount > positiveCount) return 'negative';
        return 'neutral';
    }

    /**
     * Évalue l'urgence
     */
    assessUrgency(text) {
        const urgent = ['urgent', 'immédiatement', 'maintenant', 'vite', 'dès que possible', 'aujourd\'hui'];
        return urgent.some(word => text.includes(word)) ? 'high' : 'normal';
    }

    /**
     * Génère une réponse
     */
    async generateResponse(analysis) {
        const intent = analysis.intent.intent;
        
        switch (intent) {
            case 'search_events':
                return await this.handleEventSearch(analysis);
            case 'event_details':
                return await this.handleEventDetails(analysis);
            case 'registration':
                return await this.handleRegistration(analysis);
            case 'calendar':
                return await this.handleCalendar(analysis);
            case 'recommendations':
                return await this.handleRecommendations(analysis);
            case 'help':
                return await this.handleHelp(analysis);
            case 'general_chat':
                return this.handleGeneralChat(analysis);
            default:
                return this.generateFallbackResponse(analysis.original);
        }
    }

    /**
     * Gère la recherche d'événements
     */
    async handleEventSearch(analysis) {
        const filters = this.buildEventFilters(analysis);
        const events = await this.searchEvents(filters);
        
        if (events.length === 0) {
            return {
                text: 'Je n\'ai trouvé aucun événement correspondant à votre recherche. Voulez-vous essayer avec d\'autres critères ?',
                type: 'no_results',
                suggestions: ['Élargir les critères', 'Voir tous les événements', 'Changer de période']
            };
        }

        const response = this.formatEventList(events);
        
        return {
            text: response.text,
            type: 'event_list',
            events: events,
            total: events.length,
            suggestions: ['Voir les détails', 'S\'inscrire', 'Filtrer davantage']
        };
    }

    /**
     * Gère les détails d'événement
     */
    async handleEventDetails(analysis) {
        const eventId = this.extractEventId(analysis);
        
        if (!eventId) {
            return {
                text: 'Pourriez-vous me dire de quel événement vous souhaitez plus de détails ?',
                type: 'clarification_needed',
                suggestions: ['Rechercher des événements', 'Voir les événements récents']
            };
        }

        const event = await this.getEventDetails(eventId);
        
        if (!event) {
            return {
                text: 'Je ne trouve pas cet événement. Voulez-vous rechercher dans la liste des événements disponibles ?',
                type: 'event_not_found',
                suggestions: ['Rechercher des événements', 'Voir tous les événements']
            };
        }

        const response = this.formatEventDetails(event);
        
        return {
            text: response.text,
            type: 'event_details',
            event: event,
            suggestions: ['S\'inscrire', 'Ajouter au calendrier', 'Partager']
        };
    }

    /**
     * Gère l'inscription
     */
    async handleRegistration(analysis) {
        const eventId = this.extractEventId(analysis);
        
        if (!eventId) {
            return {
                text: 'Pour quel événement souhaitez-vous vous inscrire ?',
                type: 'clarification_needed',
                suggestions: ['Voir les événements disponibles', 'Rechercher un événement']
            };
        }

        try {
            const result = await this.registerForEvent(eventId);
            
            if (result.success) {
                return {
                    text: `✅ Vous êtes bien inscrit à l'événement ! Vous recevrez une confirmation par email.`,
                    type: 'registration_success',
                    event: result.event,
                    suggestions: ['Voir mon calendrier', 'Inviter des amis', 'Préparer pour l\'événement']
                };
            } else {
                return {
                    text: `❌ Impossible de s'inscrire : ${result.message}`,
                    type: 'registration_failed',
                    suggestions: ['Réessayer', 'Contacter le support', 'Voir les détails']
                };
            }
        } catch (error) {
            return {
                text: 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer plus tard.',
                type: 'registration_error',
                suggestions: ['Réessayer', 'Contacter le support']
            };
        }
    }

    /**
     * Gère le calendrier
     */
    async handleCalendar(analysis) {
        const timeframe = analysis.timeframe;
        const events = await this.getCalendarEvents(timeframe);
        
        if (events.length === 0) {
            return {
                text: `Aucun événement trouvé pour ${this.getTimeframeText(timeframe)}.`,
                type: 'no_calendar_events',
                suggestions: ['Voir tous les événements', 'Changer de période']
            };
        }

        const response = this.formatCalendarEvents(events, timeframe);
        
        return {
            text: response.text,
            type: 'calendar_view',
            events: events,
            timeframe: timeframe,
            suggestions: ['S\'inscrire', 'Voir les détails', 'Exporter le calendrier']
        };
    }

    /**
     * Gère les recommandations
     */
    async handleRecommendations(analysis) {
        const recommendations = await this.getPersonalizedRecommendations(analysis);
        
        if (recommendations.length === 0) {
            return {
                text: 'Je n\'ai pas de recommandations personnalisées pour le moment. Voulez-vous voir les événements populaires ?',
                type: 'no_recommendations',
                suggestions: ['Voir les événements populaires', 'Mettre à jour mes préférences']
            };
        }

        const response = this.formatRecommendations(recommendations);
        
        return {
            text: response.text,
            type: 'recommendations',
            recommendations: recommendations,
            suggestions: ['Voir les détails', 'S\'inscrire', 'Plus de recommandations']
        };
    }

    /**
     * Gère l'aide
     */
    async handleHelp(analysis) {
        const helpTopics = [
            'Rechercher des événements',
            'S\'inscrire à un événement',
            'Voir mon calendrier',
            'Gérer mes inscriptions',
            'Contacter le support'
        ];

        return {
            text: 'Je peux vous aider avec :\n\n' + helpTopics.map((topic, index) => `${index + 1}. ${topic}`).join('\n'),
            type: 'help_menu',
            topics: helpTopics,
            suggestions: helpTopics
        };
    }

    /**
     * Gère le chat général
     */
    handleGeneralChat(analysis) {
        const greetings = {
            'salut': 'Bonjour ! Comment puis-je vous aider avec les événements ESEN ?',
            'bonjour': 'Bonjour ! Je suis là pour vous aider avec les événements. Que recherchez-vous ?',
            'merci': 'Je vous en prie ! N\'hésitez pas si vous avez d\'autres questions.',
            'au revoir': 'Au revoir ! N\'hésitez pas à revenir pour découvrir nos événements.'
        };

        const text = analysis.original.toLowerCase();
        
        for (const [greeting, response] of Object.entries(greetings)) {
            if (text.includes(greeting)) {
                return {
                    text: response,
                    type: 'greeting',
                    suggestions: ['Rechercher des événements', 'Voir le calendrier', 'Besoin d\'aide ?']
                };
            }
        }

        return {
            text: 'Je suis spécialisé dans les événements ESEN. Comment puis-je vous aider ?',
            type: 'general',
            suggestions: ['Rechercher des événements', 'Voir les événements populaires', 'Besoin d\'aide ?']
        };
    }

    /**
     * Construit les filtres d'événements
     */
    buildEventFilters(analysis) {
        return {
            category: analysis.category,
            timeframe: analysis.timeframe,
            location: analysis.location,
            keywords: analysis.entities.keywords,
            urgency: analysis.urgency
        };
    }

    /**
     * Recherche des événements
     */
    async searchEvents(filters) {
        // Simulation de recherche
        const allEvents = await this.getAllEvents();
        
        return allEvents.filter(event => {
            if (filters.category !== 'general' && !event.category.includes(filters.category)) {
                return false;
            }
            
            if (filters.timeframe !== 'any' && !this.matchesTimeframe(event, filters.timeframe)) {
                return false;
            }
            
            if (filters.location !== 'any' && !event.location.includes(filters.location)) {
                return false;
            }
            
            if (filters.keywords.length > 0) {
                const eventText = `${event.title} ${event.description}`.toLowerCase();
                if (!filters.keywords.some(keyword => eventText.includes(keyword))) {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Vérifie si un événement correspond à une période
     */
    matchesTimeframe(event, timeframe) {
        const eventDate = new Date(event.date);
        const now = new Date();
        
        switch (timeframe) {
            case 'today':
                return eventDate.toDateString() === now.toDateString();
            case 'tomorrow':
                const tomorrow = new Date(now);
                tomorrow.setDate(tomorrow.getDate() + 1);
                return eventDate.toDateString() === tomorrow.toDateString();
            case 'this_week':
                const weekStart = new Date(now);
                weekStart.setDate(weekStart.getDate() - weekStart.getDay());
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekEnd.getDate() + 6);
                return eventDate >= weekStart && eventDate <= weekEnd;
            case 'upcoming':
                return eventDate > now;
            default:
                return true;
        }
    }

    /**
     * Extrait l'ID d'événement
     */
    extractEventId(analysis) {
        // Recherche de nombres dans le message
        const numbers = analysis.entities.numbers;
        if (numbers.length > 0) {
            return numbers[0];
        }
        
        // Recherche de mots-clés spécifiques
        const text = analysis.original.toLowerCase();
        if (text.includes('premier') || text.includes('1er')) return 1;
        if (text.includes('deuxième') || text.includes('2ème')) return 2;
        if (text.includes('troisième') || text.includes('3ème')) return 3;
        
        return null;
    }

    /**
     * Obtient les détails d'un événement
     */
    async getEventDetails(eventId) {
        const allEvents = await this.getAllEvents();
        return allEvents.find(event => event.id === eventId);
    }

    /**
     * Formate la liste d'événements
     */
    formatEventList(events) {
        if (events.length === 0) {
            return { text: 'Aucun événement trouvé.' };
        }

        const eventList = events.slice(0, 5).map((event, index) => 
            `${index + 1}. **${event.title}**\n   📅 ${event.date}\n   📍 ${event.location}\n   📝 ${event.description.substring(0, 100)}...`
        ).join('\n\n');

        return {
            text: `J'ai trouvé ${events.length} événement(s) :\n\n${eventList}${events.length > 5 ? '\n\n... et d\'autres événements' : ''}`
        };
    }

    /**
     * Formate les détails d'un événement
     */
    formatEventDetails(event) {
        return {
            text: `**${event.title}**\n\n📅 **Date :** ${event.date}\n⏰ **Heure :** ${event.time}\n📍 **Lieu :** ${event.location}\n👥 **Participants :** ${event.participants}/${event.capacity}\n\n📝 **Description :**\n${event.description}\n\n**Catégorie :** ${event.category}\n**Organisateur :** ${event.organizer}`
        };
    }

    /**
     * S'inscrit à un événement
     */
    async registerForEvent(eventId) {
        // Simulation d'inscription
        const event = await this.getEventDetails(eventId);
        
        if (!event) {
            return { success: false, message: 'Événement non trouvé' };
        }
        
        if (event.participants >= event.capacity) {
            return { success: false, message: 'Événement complet' };
        }
        
        // Simulation d'inscription réussie
        return { success: true, event: event };
    }

    /**
     * Obtient les événements du calendrier
     */
    async getCalendarEvents(timeframe) {
        const allEvents = await this.getAllEvents();
        return allEvents.filter(event => this.matchesTimeframe(event, timeframe));
    }

    /**
     * Formate les événements du calendrier
     */
    formatCalendarEvents(events, timeframe) {
        const timeframeText = this.getTimeframeText(timeframe);
        
        if (events.length === 0) {
            return { text: `Aucun événement prévu ${timeframeText}.` };
        }

        const eventList = events.map(event => 
            `📅 ${event.date} - **${event.title}** (${event.time})`
        ).join('\n');

        return {
            text: `Voici les événements prévus ${timeframeText} :\n\n${eventList}`
        };
    }

    /**
     * Obtient le texte de la période
     */
    getTimeframeText(timeframe) {
        const texts = {
            today: 'aujourd\'hui',
            tomorrow: 'demain',
            this_week: 'cette semaine',
            next_week: 'la semaine prochaine',
            this_month: 'ce mois',
            upcoming: 'à venir',
            any: ''
        };
        
        return texts[timeframe] || '';
    }

    /**
     * Obtient des recommandations personnalisées
     */
    async getPersonalizedRecommendations(analysis) {
        // Simulation de recommandations basées sur les préférences utilisateur
        const allEvents = await this.getAllEvents();
        
        // Recommandations basées sur la catégorie et les mots-clés
        return allEvents
            .filter(event => {
                const categoryMatch = analysis.category === 'general' || event.category === analysis.category;
                const keywordMatch = analysis.entities.keywords.some(keyword => 
                    event.title.toLowerCase().includes(keyword) || 
                    event.description.toLowerCase().includes(keyword)
                );
                return categoryMatch || keywordMatch;
            })
            .slice(0, 5);
    }

    /**
     * Formate les recommandations
     */
    formatRecommendations(recommendations) {
        if (recommendations.length === 0) {
            return { text: 'Aucune recommandation disponible.' };
        }

        const recList = recommendations.map((event, index) => 
            `${index + 1}. **${event.title}**\n   📅 ${event.date} - ${event.location}`
        ).join('\n\n');

        return {
            text: `Voici mes recommandations pour vous :\n\n${recList}`
        };
    }

    /**
     * Génère des suggestions
     */
    generateSuggestions(analysis) {
        const suggestions = [];
        
        switch (analysis.intent.intent) {
            case 'search_events':
                suggestions.push('Affiner la recherche', 'Voir les détails', 'S\'inscrire');
                break;
            case 'event_details':
                suggestions.push('S\'inscrire', 'Ajouter au calendrier', 'Partager');
                break;
            case 'general_chat':
                suggestions.push('Rechercher des événements', 'Voir le calendrier', 'Besoin d\'aide ?');
                break;
            default:
                suggestions.push('Comment puis-je vous aider ?', 'Voir les événements', 'Besoin d\'aide ?');
        }
        
        return suggestions;
    }

    /**
     * Trouve des événements connexes
     */
    findRelatedEvents(analysis) {
        // Simulation d'événements connexes
        return [];
    }

    /**
     * Met à jour le contexte
     */
    updateContext(analysis, response) {
        this.currentContext = {
            lastIntent: analysis.intent,
            lastEntities: analysis.entities,
            lastResponse: response.type,
            timestamp: new Date().toISOString()
        };
        
        this.conversationHistory.push({
            user: analysis.original,
            bot: response.text,
            timestamp: new Date().toISOString()
        });
        
        // Limiter l'historique à 10 messages
        if (this.conversationHistory.length > 10) {
            this.conversationHistory = this.conversationHistory.slice(-10);
        }
    }

    /**
     * Génère une réponse de secours
     */
    generateFallbackResponse(message) {
        return {
            text: 'Je ne suis pas sûr de comprendre. Pouvez-vous me dire ce que vous cherchez concernant les événements ESEN ?',
            type: 'fallback',
            suggestions: ['Rechercher des événements', 'Voir le calendrier', 'Besoin d\'aide ?']
        };
    }

    /**
     * Charge la base de données d'événements
     */
    async loadEventDatabase() {
        // Simulation de chargement
        this.eventDatabase = await this.getAllEvents();
    }

    /**
     * Charge les préférences utilisateur
     */
    async loadUserPreferences() {
        // Simulation de chargement
        this.userPreferences = {
            categories: ['technical', 'business'],
            timeframe: 'upcoming',
            location: 'any'
        };
    }

    /**
     * Obtient tous les événements
     */
    async getAllEvents() {
        // Simulation de données
        return [
            {
                id: 1,
                title: 'Conférence sur l\'IA',
                description: 'Une conférence fascinante sur les dernières tendances en intelligence artificielle',
                date: '2024-01-15',
                time: '14:00',
                location: 'Amphi A - ESEN',
                category: 'technical',
                participants: 45,
                capacity: 100,
                organizer: 'Département Informatique'
            },
            {
                id: 2,
                title: 'Atelier de Networking',
                description: 'Rencontrez des professionnels et développez votre réseau',
                date: '2024-01-20',
                time: '18:00',
                location: 'Salle B - ESEN',
                category: 'networking',
                participants: 30,
                capacity: 50,
                organizer: 'ESEN Connect'
            },
            {
                id: 3,
                title: 'Workshop Marketing Digital',
                description: 'Apprenez les techniques de marketing digital moderne',
                date: '2024-01-25',
                time: '10:00',
                location: 'En ligne',
                category: 'business',
                participants: 75,
                capacity: 200,
                organizer: 'Département Marketing'
            }
        ];
    }

    /**
     * Configure les gestionnaires d'événements
     */
    setupEventHandlers() {
        document.addEventListener('events-chatbot:message', async (event) => {
            const { message, context } = event.detail;
            const response = await this.processMessage(message, context);
            
            document.dispatchEvent(new CustomEvent('events-chatbot:response', {
                detail: response
            }));
        });
    }
}

// Export de la classe
window.EventsChatbot = EventsChatbot;

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    window.eventsChatbot = new EventsChatbot();
});
