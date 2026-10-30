/**
 * ESEN Connect - Advanced AI Engine
 * Moteur IA avancé pour le chatbot avec NLP et machine learning
 */

class AdvancedAIEngine {
    constructor() {
        this.apiEndpoint = '/api/ai-chat';
        this.modelVersion = '2.0';
        this.contextWindow = 10;
        this.confidenceThreshold = 0.7;
        this.responseDelay = { min: 800, max: 2000 };
        this.personality = {
            name: 'ESEN Assistant',
            traits: ['helpful', 'professional', 'friendly', 'knowledgeable'],
            expertise: ['ESEN', 'education', 'technology', 'career guidance'],
            language: 'fr'
        };
        
        this.initializeEngine();
    }

    /**
     * Initialise le moteur IA
     */
    initializeEngine() {
        this.loadModel();
        this.setupEventListeners();
        this.initializeKnowledgeBase();
        console.log('🤖 Advanced AI Engine initialized');
    }

    /**
     * Charge le modèle IA
     */
    async loadModel() {
        try {
            // Simulation du chargement du modèle
            this.model = await this.loadNeuralNetwork();
            this.tokenizer = await this.loadTokenizer();
            this.embeddings = await this.loadEmbeddings();
            
            console.log('✅ AI Model loaded successfully');
        } catch (error) {
            console.error('❌ Failed to load AI model:', error);
            this.fallbackToBasicMode();
        }
    }

    /**
     * Traite un message utilisateur
     */
    async processMessage(userMessage, conversationHistory = []) {
        const startTime = Date.now();
        
        try {
            // Prétraitement du message
            const preprocessedMessage = this.preprocessMessage(userMessage);
            
            // Analyse de l'intention
            const intent = await this.analyzeIntent(preprocessedMessage);
            
            // Génération de la réponse
            const response = await this.generateResponse(preprocessedMessage, intent, conversationHistory);
            
            // Post-traitement
            const finalResponse = this.postprocessResponse(response);
            
            // Simulation du temps de réponse humain
            const processingTime = Date.now() - startTime;
            const delay = Math.max(0, this.calculateDelay(processingTime));
            
            return {
                response: finalResponse,
                intent: intent,
                confidence: response.confidence,
                processingTime: processingTime,
                delay: delay,
                metadata: {
                    model: this.modelVersion,
                    timestamp: new Date().toISOString(),
                    context: this.extractContext(preprocessedMessage)
                }
            };
            
        } catch (error) {
            console.error('Error processing message:', error);
            return this.generateFallbackResponse(userMessage);
        }
    }

    /**
     * Prétraitement du message
     */
    preprocessMessage(message) {
        return {
            original: message,
            cleaned: this.cleanText(message),
            tokens: this.tokenize(message),
            entities: this.extractEntities(message),
            sentiment: this.analyzeSentiment(message),
            language: this.detectLanguage(message)
        };
    }

    /**
     * Analyse l'intention du message
     */
    async analyzeIntent(preprocessedMessage) {
        const intents = [
            { name: 'greeting', patterns: ['bonjour', 'salut', 'hello', 'hi'], confidence: 0.9 },
            { name: 'question', patterns: ['?', 'comment', 'pourquoi', 'quoi', 'où'], confidence: 0.8 },
            { name: 'help', patterns: ['aide', 'help', 'support', 'assistance'], confidence: 0.85 },
            { name: 'information', patterns: ['info', 'information', 'détails', 'savoir'], confidence: 0.8 },
            { name: 'farewell', patterns: ['au revoir', 'bye', 'à bientôt', 'goodbye'], confidence: 0.9 },
            { name: 'esen_related', patterns: ['esen', 'école', 'formation', 'cours'], confidence: 0.85 },
            { name: 'career', patterns: ['carrière', 'emploi', 'stage', 'travail'], confidence: 0.8 },
            { name: 'technical', patterns: ['code', 'programmation', 'développement', 'tech'], confidence: 0.75 }
        ];

        let bestMatch = { name: 'unknown', confidence: 0 };

        for (const intent of intents) {
            const confidence = this.calculateIntentConfidence(preprocessedMessage.cleaned, intent.patterns);
            if (confidence > bestMatch.confidence) {
                bestMatch = { name: intent.name, confidence };
            }
        }

        return bestMatch;
    }

    /**
     * Génère une réponse
     */
    async generateResponse(preprocessedMessage, intent, conversationHistory) {
        const responses = this.getResponseTemplates(intent.name);
        const context = this.buildContext(conversationHistory);
        
        // Sélection de la meilleure réponse
        let response = this.selectBestResponse(responses, preprocessedMessage, context);
        
        // Personnalisation de la réponse
        response = this.personalizeResponse(response, preprocessedMessage);
        
        // Ajout de suggestions
        const suggestions = this.generateSuggestions(intent, preprocessedMessage);
        
        return {
            text: response,
            suggestions: suggestions,
            confidence: intent.confidence,
            type: intent.name
        };
    }

    /**
     * Post-traitement de la réponse
     */
    postprocessResponse(response) {
        return {
            ...response,
            text: this.formatResponse(response.text),
            timestamp: new Date().toISOString(),
            id: this.generateResponseId()
        };
    }

    /**
     * Nettoie le texte
     */
    cleanText(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^\w\sàáâäéèêëìíîïòóôöùúûüñç]/g, '')
            .replace(/\s+/g, ' ');
    }

    /**
     * Tokenisation du texte
     */
    tokenize(text) {
        // Tokenisation simplifiée
        return text.split(' ').filter(token => token.length > 0);
    }

    /**
     * Extrait les entités
     */
    extractEntities(text) {
        const entities = [];
        
        // Extraction des entités ESEN
        const esenTerms = ['esen', 'école', 'campus', 'formation'];
        const careerTerms = ['emploi', 'stage', 'carrière', 'entreprise'];
        const techTerms = ['programmation', 'développement', 'code', 'technologie'];
        
        esenTerms.forEach(term => {
            if (text.toLowerCase().includes(term)) {
                entities.push({ type: 'organization', value: term, confidence: 0.9 });
            }
        });
        
        careerTerms.forEach(term => {
            if (text.toLowerCase().includes(term)) {
                entities.push({ type: 'career', value: term, confidence: 0.8 });
            }
        });
        
        techTerms.forEach(term => {
            if (text.toLowerCase().includes(term)) {
                entities.push({ type: 'technology', value: term, confidence: 0.8 });
            }
        });
        
        return entities;
    }

    /**
     * Analyse le sentiment
     */
    analyzeSentiment(text) {
        const positiveWords = ['bon', 'excellent', 'super', 'génial', 'parfait', 'merci'];
        const negativeWords = ['mauvais', 'problème', 'erreur', 'difficile', 'compliqué'];
        
        const positiveCount = positiveWords.filter(word => text.toLowerCase().includes(word)).length;
        const negativeCount = negativeWords.filter(word => text.toLowerCase().includes(word)).length;
        
        if (positiveCount > negativeCount) return 'positive';
        if (negativeCount > positiveCount) return 'negative';
        return 'neutral';
    }

    /**
     * Détecte la langue
     */
    detectLanguage(text) {
        const frenchWords = ['bonjour', 'merci', 'au revoir', 's\'il vous plaît'];
        const englishWords = ['hello', 'thank you', 'goodbye', 'please'];
        
        const frenchCount = frenchWords.filter(word => text.toLowerCase().includes(word)).length;
        const englishCount = englishWords.filter(word => text.toLowerCase().includes(word)).length;
        
        if (frenchCount > englishCount) return 'fr';
        if (englishCount > frenchCount) return 'en';
        return 'fr'; // Par défaut français
    }

    /**
     * Calcule la confiance de l'intention
     */
    calculateIntentConfidence(text, patterns) {
        const matches = patterns.filter(pattern => text.includes(pattern)).length;
        return matches / patterns.length;
    }

    /**
     * Obtient les templates de réponse
     */
    getResponseTemplates(intent) {
        const templates = {
            greeting: [
                'Bonjour ! Je suis votre assistant ESEN. Comment puis-je vous aider ?',
                'Salut ! Bienvenue sur ESEN Connect. En quoi puis-je vous assister ?',
                'Hello ! Je suis là pour répondre à vos questions sur ESEN.'
            ],
            question: [
                'C\'est une excellente question ! Laissez-moi vous aider...',
                'Je comprends votre interrogation. Voici ce que je peux vous dire...',
                'Permettez-moi de clarifier ce point pour vous...'
            ],
            help: [
                'Je suis là pour vous aider ! Dites-moi ce dont vous avez besoin.',
                'Comment puis-je vous assister aujourd\'hui ?',
                'N\'hésitez pas à me poser votre question, je suis là pour ça.'
            ],
            information: [
                'Voici les informations que je peux vous fournir...',
                'Laissez-moi partager avec vous ce que je sais...',
                'Je vais vous donner les détails nécessaires...'
            ],
            farewell: [
                'Au revoir ! N\'hésitez pas à revenir si vous avez d\'autres questions.',
                'À bientôt ! J\'espère avoir pu vous aider.',
                'Bonne journée ! La porte reste ouverte pour vos questions.'
            ],
            esen_related: [
                'ESEN est une excellente école ! Voici ce que je peux vous en dire...',
                'L\'École Supérieure d\'Économie Numérique offre de nombreuses opportunités...',
                'ESEN Connect est la plateforme qui relie étudiants et entreprises...'
            ],
            career: [
                'Votre carrière est importante ! Voici quelques conseils...',
                'Le monde professionnel offre de nombreuses opportunités...',
                'Pour votre développement de carrière, je vous suggère...'
            ],
            technical: [
                'La technologie est un domaine passionnant ! Voici ce que je sais...',
                'Le développement et la programmation ouvrent beaucoup de portes...',
                'Les compétences techniques sont très recherchées aujourd\'hui...'
            ],
            unknown: [
                'Je ne suis pas certain de comprendre. Pouvez-vous reformuler ?',
                'Intéressant ! Pouvez-vous me donner plus de détails ?',
                'Hmm, laissez-moi réfléchir à la meilleure façon de vous aider...'
            ]
        };
        
        return templates[intent] || templates.unknown;
    }

    /**
     * Sélectionne la meilleure réponse
     */
    selectBestResponse(responses, preprocessedMessage, context) {
        // Sélection aléatoire pondérée par le contexte
        const weights = responses.map((response, index) => {
            const contextScore = this.calculateContextScore(response, context);
            const sentimentScore = this.calculateSentimentScore(response, preprocessedMessage.sentiment);
            return contextScore + sentimentScore + Math.random() * 0.3;
        });
        
        const bestIndex = weights.indexOf(Math.max(...weights));
        return responses[bestIndex];
    }

    /**
     * Personnalise la réponse
     */
    personalizeResponse(response, preprocessedMessage) {
        let personalized = response;
        
        // Ajout des entités détectées
        if (preprocessedMessage.entities.length > 0) {
            const mainEntity = preprocessedMessage.entities[0];
            personalized += ` Je vois que vous parlez de ${mainEntity.value}.`;
        }
        
        // Adaptation selon le sentiment
        if (preprocessedMessage.sentiment === 'positive') {
            personalized += ' 😊';
        } else if (preprocessedMessage.sentiment === 'negative') {
            personalized += ' Je suis là pour vous aider !';
        }
        
        return personalized;
    }

    /**
     * Génère des suggestions
     */
    generateSuggestions(intent, preprocessedMessage) {
        const suggestions = {
            greeting: ['Parler de ESEN', 'Poser une question', 'Demander de l\'aide'],
            question: ['En savoir plus sur ESEN', 'Carrières et stages', 'Support technique'],
            help: ['Guide ESEN', 'Contact support', 'FAQ'],
            information: ['Programmes ESEN', 'Entreprises partenaires', 'Événements'],
            esen_related: ['Formations', 'Admission', 'Campus'],
            career: ['Stages', 'Emplois', 'Compétences'],
            technical: ['Technologies', 'Projets', 'Ressources'],
            default: ['Poser une question', 'En savoir plus', 'Contacter support']
        };
        
        return suggestions[intent] || suggestions.default;
    }

    /**
     * Formate la réponse
     */
    formatResponse(text) {
        return text
            .replace(/\b(\w+)\b/g, (match) => {
                // Mise en majuscule des premières lettres
                return match.charAt(0).toUpperCase() + match.slice(1);
            })
            .replace(/\s+/g, ' ')
            .trim();
    }

    /**
     * Génère un ID de réponse unique
     */
    generateResponseId() {
        return 'resp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Calcule le délai de réponse
     */
    calculateDelay(processingTime) {
        const baseDelay = Math.random() * (this.responseDelay.max - this.responseDelay.min) + this.responseDelay.min;
        return Math.max(0, baseDelay - processingTime);
    }

    /**
     * Extrait le contexte
     */
    extractContext(preprocessedMessage) {
        return {
            entities: preprocessedMessage.entities,
            sentiment: preprocessedMessage.sentiment,
            language: preprocessedMessage.language,
            tokenCount: preprocessedMessage.tokens.length
        };
    }

    /**
     * Construit le contexte de conversation
     */
    buildContext(conversationHistory) {
        return conversationHistory.slice(-this.contextWindow);
    }

    /**
     * Calcule le score de contexte
     */
    calculateContextScore(response, context) {
        // Score basé sur la pertinence contextuelle
        return Math.random() * 0.5; // Simplifié pour l'exemple
    }

    /**
     * Calcule le score de sentiment
     */
    calculateSentimentScore(response, sentiment) {
        // Score basé sur la correspondance de sentiment
        return Math.random() * 0.3; // Simplifié pour l'exemple
    }

    /**
     * Génère une réponse de secours
     */
    generateFallbackResponse(userMessage) {
        return {
            response: {
                text: 'Je suis désolé, je rencontre des difficultés à traiter votre demande. Pouvez-vous reformuler ?',
                suggestions: ['Contacter le support', 'Essayer plus tard', 'Question simple'],
                confidence: 0.3,
                type: 'fallback'
            },
            intent: { name: 'fallback', confidence: 0.3 },
            processingTime: 0,
            delay: 500,
            metadata: {
                model: this.modelVersion,
                timestamp: new Date().toISOString(),
                fallback: true
            }
        };
    }

    /**
     * Mode de secours basique
     */
    fallbackToBasicMode() {
        console.log('🔄 Switching to basic mode');
        this.model = null;
        this.basicMode = true;
    }

    /**
     * Configure les écouteurs d'événements
     */
    setupEventListeners() {
        // Écouteurs pour les événements du chatbot
        document.addEventListener('ai:message', this.handleMessage.bind(this));
        document.addEventListener('ai:typing', this.handleTyping.bind(this));
    }

    /**
     * Gère les messages entrants
     */
    async handleMessage(event) {
        const { message, history } = event.detail;
        const response = await this.processMessage(message, history);
        
        // Émet l'événement de réponse
        document.dispatchEvent(new CustomEvent('ai:response', {
            detail: response
        }));
    }

    /**
     * Gère l'état de frappe
     */
    handleTyping(event) {
        const { isTyping } = event.detail;
        
        // Émet l'événement de frappe
        document.dispatchEvent(new CustomEvent('ai:typing-indicator', {
            detail: { isTyping }
        }));
    }

    /**
     * Charge le réseau neuronal (simulation)
     */
    async loadNeuralNetwork() {
        // Simulation du chargement du modèle
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    layers: [128, 64, 32],
                    activation: 'relu',
                    optimizer: 'adam',
                    version: '2.0'
                });
            }, 1000);
        });
    }

    /**
     * Charge le tokenizer (simulation)
     */
    async loadTokenizer() {
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    vocabSize: 10000,
                    maxLength: 512,
                    type: 'bpe'
                });
            }, 500);
        });
    }

    /**
     * Charge les embeddings (simulation)
     */
    async loadEmbeddings() {
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    dimension: 768,
                    model: 'sentence-transformers',
                    language: 'fr'
                });
            }, 800);
        });
    }

    /**
     * Initialise la base de connaissances
     */
    initializeKnowledgeBase() {
        this.knowledgeBase = {
            esen: {
                description: 'École Supérieure d\'Économie Numérique',
                programs: ['Digital Marketing', 'Data Science', 'Web Development'],
                location: 'Tunis, Tunisie'
            },
            careers: {
                opportunities: ['Stage', 'Alternance', 'CDI', 'Freelance'],
                skills: ['Programmation', 'Marketing Digital', 'Data Analysis'],
                companies: ['Startups', 'Grandes entreprises', 'Agences']
            }
        };
    }
}

// Export de la classe
window.AdvancedAIEngine = AdvancedAIEngine;

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    window.aiEngine = new AdvancedAIEngine();
});
