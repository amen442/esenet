/**
 * ESEN Connect - AI Event Analyzer
 * Analyseur d'événements IA pour recommandations personnalisées
 */

class AIEventAnalyzer {
    constructor() {
        this.apiEndpoint = '/api/events/analyze';
        this.modelVersion = '1.5';
        this.categories = {
            technical: ['programmation', 'développement', 'code', 'tech', 'software'],
            business: ['business', 'entreprise', 'marketing', 'finance', 'management'],
            creative: ['design', 'créativité', 'art', 'multimédia', 'innovation'],
            networking: ['réseau', 'contact', 'carrière', 'opportunité', 'collaboration'],
            academic: ['étude', 'recherche', 'formation', 'pédagogie', 'apprentissage']
        };
        
        this.userProfile = null;
        this.eventHistory = [];
        this.preferences = {};
        
        this.initializeAnalyzer();
    }

    /**
     * Initialise l'analyseur
     */
    async initializeAnalyzer() {
        await this.loadUserProfile();
        this.loadEventHistory();
        this.setupEventListeners();
        console.log('🧠 AI Event Analyzer initialized');
    }

    /**
     * Analyse un événement
     */
    async analyzeEvent(eventData) {
        try {
            const analysis = {
                basic: this.performBasicAnalysis(eventData),
                categorization: this.categorizeEvent(eventData),
                relevance: this.calculateRelevance(eventData),
                recommendations: this.generateRecommendations(eventData),
                insights: this.extractInsights(eventData),
                prediction: this.predictEngagement(eventData)
            };

            // Enrichissement avec l'IA
            const aiEnrichment = await this.enrichWithAI(eventData, analysis);
            
            return {
                ...analysis,
                ai: aiEnrichment,
                timestamp: new Date().toISOString(),
                confidence: this.calculateConfidence(analysis)
            };
            
        } catch (error) {
            console.error('Error analyzing event:', error);
            return this.generateFallbackAnalysis(eventData);
        }
    }

    /**
     * Analyse basique d'un événement
     */
    performBasicAnalysis(eventData) {
        return {
            title: this.extractKeywords(eventData.titre),
            description: this.extractKeywords(eventData.description),
            complexity: this.assessComplexity(eventData),
            duration: this.estimateDuration(eventData),
            type: this.determineEventType(eventData),
            audience: this.identifyAudience(eventData)
        };
    }

    /**
     * Catégorise l'événement
     */
    categorizeEvent(eventData) {
        const text = `${eventData.titre} ${eventData.description}`.toLowerCase();
        const scores = {};
        
        for (const [category, keywords] of Object.entries(this.categories)) {
            scores[category] = this.calculateCategoryScore(text, keywords);
        }
        
        const sortedCategories = Object.entries(scores)
            .sort(([,a], [,b]) => b - a)
            .slice(0, 3);
        
        return {
            primary: sortedCategories[0][0],
            secondary: sortedCategories[1]?.[0],
            scores: scores,
            confidence: sortedCategories[0][1]
        };
    }

    /**
     * Calcule la pertinence pour l'utilisateur
     */
    calculateRelevance(eventData) {
        if (!this.userProfile) {
            return { score: 0.5, reasons: ['Profil utilisateur non disponible'] };
        }

        const factors = {
            interests: this.calculateInterestMatch(eventData),
            skills: this.calculateSkillAlignment(eventData),
            career: this.calculateCareerRelevance(eventData),
            schedule: this.calculateScheduleFit(eventData),
            location: this.calculateLocationRelevance(eventData)
        };

        const weights = {
            interests: 0.3,
            skills: 0.25,
            career: 0.25,
            schedule: 0.1,
            location: 0.1
        };

        const score = Object.entries(factors).reduce((total, [factor, value]) => {
            return total + (value.score * weights[factor]);
        }, 0);

        const reasons = Object.entries(factors)
            .filter(([, value]) => value.score > 0.6)
            .map(([factor, value]) => value.reason);

        return {
            score: Math.min(1, score),
            factors: factors,
            reasons: reasons.length > 0 ? reasons : ['Pertinence modérée']
        };
    }

    /**
     * Génère des recommandations
     */
    generateRecommendations(eventData) {
        const recommendations = [];

        // Recommandations basées sur le profil
        if (this.userProfile) {
            recommendations.push(...this.generateProfileBasedRecommendations(eventData));
        }

        // Recommandations basées sur l'historique
        recommendations.push(...this.generateHistoryBasedRecommendations(eventData));

        // Recommandations basées sur les tendances
        recommendations.push(...this.generateTrendBasedRecommendations(eventData));

        // Recommandations basées sur le réseau
        recommendations.push(...this.generateNetworkBasedRecommendations(eventData));

        return recommendations.slice(0, 5); // Top 5 recommandations
    }

    /**
     * Extrait des insights
     */
    extractInsights(eventData) {
        const insights = [];

        // Insight sur la popularité
        if (eventData.participants && eventData.capacity) {
            const fillRate = eventData.participants / eventData.capacity;
            if (fillRate > 0.8) {
                insights.push({
                    type: 'popularity',
                    message: 'Cet événement est très populaire',
                    confidence: 0.9
                });
            }
        }

        // Insight sur le timing
        const eventDate = new Date(eventData.dateEvent);
        const now = new Date();
        const daysUntil = Math.ceil((eventDate - now) / (1000 * 60 * 60 * 24));
        
        if (daysUntil < 7) {
            insights.push({
                type: 'urgency',
                message: 'Cet événement approche rapidement',
                confidence: 0.95
            });
        }

        // Insight sur la catégorie
        const categorization = this.categorizeEvent(eventData);
        if (categorization.confidence > 0.8) {
            insights.push({
                type: 'category',
                message: `Événement principalement ${categorization.primary}`,
                confidence: categorization.confidence
            });
        }

        return insights;
    }

    /**
     * Prédit l'engagement
     */
    predictEngagement(eventData) {
        const features = {
            titleLength: eventData.titre.length,
            descriptionLength: eventData.description.length,
            hasDescription: eventData.description.length > 50,
            category: this.categorizeEvent(eventData).primary,
            upcoming: new Date(eventData.dateEvent) > new Date(),
            capacity: eventData.capacity || 0,
            participants: eventData.participants || 0
        };

        // Modèle de prédiction simplifié
        const weights = {
            titleLength: 0.1,
            descriptionLength: 0.15,
            hasDescription: 0.2,
            upcoming: 0.25,
            capacity: 0.15,
            participants: 0.15
        };

        let score = 0;
        for (const [feature, value] of Object.entries(features)) {
            if (typeof value === 'number') {
                score += (value / 100) * weights[feature];
            } else if (typeof value === 'boolean') {
                score += (value ? 1 : 0) * weights[feature];
            }
        }

        return {
            predictedEngagement: Math.min(1, score),
            factors: features,
            confidence: 0.75
        };
    }

    /**
     * Enrichissement avec l'IA
     */
    async enrichWithAI(eventData, analysis) {
        try {
            // Simulation d'appel API IA
            const response = await this.callAIEnrichment(eventData, analysis);
            
            return {
                summary: response.summary,
                keyTopics: response.keyTopics,
                skillRequirements: response.skillRequirements,
                careerImpact: response.careerImpact,
                networkingOpportunities: response.networkingOpportunities,
                learningOutcomes: response.learningOutcomes
            };
        } catch (error) {
            console.error('AI enrichment failed:', error);
            return this.generateFallbackEnrichment(analysis);
        }
    }

    /**
     * Calcule la confiance de l'analyse
     */
    calculateConfidence(analysis) {
        const factors = {
            categorization: analysis.categorization.confidence,
            relevance: analysis.relevance.score,
            engagement: analysis.prediction.confidence,
            insights: analysis.insights.reduce((sum, insight) => sum + insight.confidence, 0) / Math.max(1, analysis.insights.length)
        };

        return Object.values(factors).reduce((sum, value) => sum + value, 0) / Object.keys(factors).length;
    }

    /**
     * Extrait les mots-clés
     */
    extractKeywords(text) {
        if (!text) return [];
        
        const stopWords = ['le', 'la', 'les', 'de', 'des', 'du', 'au', 'aux', 'et', 'est', 'sont', 'pour', 'avec', 'sur', 'dans'];
        
        return text
            .toLowerCase()
            .replace(/[^\w\s]/g, ' ')
            .split(/\s+/)
            .filter(word => word.length > 2 && !stopWords.includes(word))
            .slice(0, 10);
    }

    /**
     * Évalue la complexité
     */
    assessComplexity(eventData) {
        const text = `${eventData.titre} ${eventData.description}`;
        const avgWordLength = text.split(/\s+/).reduce((sum, word) => sum + word.length, 0) / text.split(/\s+/).length;
        const uniqueWords = new Set(text.toLowerCase().split(/\s+/)).size;
        const totalWords = text.split(/\s+/).length;
        
        const complexity = (avgWordLength * 0.3) + ((uniqueWords / totalWords) * 0.7);
        
        if (complexity > 0.7) return 'high';
        if (complexity > 0.4) return 'medium';
        return 'low';
    }

    /**
     * Estime la durée
     */
    estimateDuration(eventData) {
        // Basé sur les mots-clés dans la description
        const durationKeywords = {
            short: ['courte', 'brief', 'rapide', '1h', '2h'],
            medium: ['demi-journée', 'après-midi', 'matin', '3h', '4h'],
            long: ['journée', 'complète', 'séminaire', 'conférence', '5h+']
        };

        const text = `${eventData.titre} ${eventData.description}`.toLowerCase();
        
        for (const [duration, keywords] of Object.entries(durationKeywords)) {
            if (keywords.some(keyword => text.includes(keyword))) {
                return duration;
            }
        }
        
        return 'medium'; // Par défaut
    }

    /**
     * Détermine le type d'événement
     */
    determineEventType(eventData) {
        const typeKeywords = {
            workshop: ['atelier', 'workshop', 'pratique', 'hands-on'],
            conference: ['conférence', 'présentation', 'talk', 'keynote'],
            networking: ['réseau', 'networking', 'rencontre', 'meetup'],
            training: ['formation', 'training', 'cours', 'learning'],
            social: ['social', 'soirée', 'célébration', 'fête']
        };

        const text = `${eventData.titre} ${eventData.description}`.toLowerCase();
        
        for (const [type, keywords] of Object.entries(typeKeywords)) {
            if (keywords.some(keyword => text.includes(keyword))) {
                return type;
            }
        }
        
        return 'other';
    }

    /**
     * Identifie l'audience
     */
    identifyAudience(eventData) {
        const audienceKeywords = {
            students: ['étudiant', 'student', 'études', 'étudiants'],
            professionals: ['professionnel', 'pro', 'experts', 'industry'],
            beginners: ['débutant', 'beginner', 'initiation', 'intro'],
            advanced: ['avancé', 'advanced', 'expert', 'master'],
            all: ['tout le monde', 'tous', 'public', 'open']
        };

        const text = `${eventData.titre} ${eventData.description}`.toLowerCase();
        
        for (const [audience, keywords] of Object.entries(audienceKeywords)) {
            if (keywords.some(keyword => text.includes(keyword))) {
                return audience;
            }
        }
        
        return 'general';
    }

    /**
     * Calcule le score de catégorie
     */
    calculateCategoryScore(text, keywords) {
        const matches = keywords.filter(keyword => text.includes(keyword)).length;
        return matches / keywords.length;
    }

    /**
     * Calcule la correspondance des intérêts
     */
    calculateInterestMatch(eventData) {
        if (!this.userProfile?.interests) return { score: 0.5, reason: 'Intérêts non définis' };
        
        const eventText = `${eventData.titre} ${eventData.description}`.toLowerCase();
        const matchingInterests = this.userProfile.interests.filter(interest => 
            eventText.includes(interest.toLowerCase())
        );
        
        const score = matchingInterests.length / Math.max(1, this.userProfile.interests.length);
        
        return {
            score: score,
            reason: score > 0.3 ? `Intérêts correspondants: ${matchingInterests.join(', ')}` : 'Peu de correspondance d\'intérêts'
        };
    }

    /**
     * Calcule l'alignement des compétences
     */
    calculateSkillAlignment(eventData) {
        if (!this.userProfile?.skills) return { score: 0.5, reason: 'Compétences non définies' };
        
        const eventText = `${eventData.titre} ${eventData.description}`.toLowerCase();
        const matchingSkills = this.userProfile.skills.filter(skill => 
            eventText.includes(skill.toLowerCase())
        );
        
        const score = matchingSkills.length / Math.max(1, this.userProfile.skills.length);
        
        return {
            score: score,
            reason: score > 0.3 ? `Compétences pertinentes: ${matchingSkills.join(', ')}` : 'Peu d\'alignement de compétences'
        };
    }

    /**
     * Calcule la pertinence carrière
     */
    calculateCareerRelevance(eventData) {
        const careerKeywords = ['carrière', 'emploi', 'stage', 'opportunité', 'recrutement'];
        const eventText = `${eventData.titre} ${eventData.description}`.toLowerCase();
        
        const matches = careerKeywords.filter(keyword => eventText.includes(keyword)).length;
        const score = matches / careerKeywords.length;
        
        return {
            score: score,
            reason: score > 0.3 ? 'Pertinent pour votre carrière' : 'Moins pertinent pour votre carrière'
        };
    }

    /**
     * Calcule l'adéquation horaire
     */
    calculateScheduleFit(eventData) {
        if (!this.userProfile?.availability) return { score: 0.5, reason: 'Disponibilité non définie' };
        
        const eventDate = new Date(eventData.dateEvent);
        const eventDay = eventDate.getDay();
        const eventHour = eventDate.getHours();
        
        // Logique simplifiée pour l'adéquation horaire
        let score = 0.5;
        let reason = 'Adéquation horaire moyenne';
        
        if (this.userProfile.availability.weekdays && eventDay >= 1 && eventDay <= 5) {
            score += 0.3;
            reason = 'Compatible avec vos disponibilités en semaine';
        }
        
        if (this.userProfile.availability.weekends && (eventDay === 0 || eventDay === 6)) {
            score += 0.3;
            reason = 'Compatible avec vos disponibilités le week-end';
        }
        
        return { score: Math.min(1, score), reason };
    }

    /**
     * Calcule la pertinence géographique
     */
    calculateLocationRelevance(eventData) {
        if (!eventData.lieu || !this.userProfile?.location) {
            return { score: 0.5, reason: 'Informations de localisation incomplètes' };
        }
        
        const userLocation = this.userProfile.location.toLowerCase();
        const eventLocation = eventData.lieu.toLowerCase();
        
        if (eventLocation.includes(userLocation) || userLocation.includes(eventLocation)) {
            return { score: 0.9, reason: 'Même localisation' };
        }
        
        // Logique de distance simplifiée
        const distance = this.calculateDistance(userLocation, eventLocation);
        if (distance < 50) {
            return { score: 0.7, reason: 'Proche de votre localisation' };
        }
        
        return { score: 0.3, reason: 'Éloigné de votre localisation' };
    }

    /**
     * Calcule la distance (simplifié)
     */
    calculateDistance(location1, location2) {
        // Simulation de calcul de distance
        return Math.random() * 100;
    }

    /**
     * Génère des recommandations basées sur le profil
     */
    generateProfileBasedRecommendations(eventData) {
        const recommendations = [];
        
        if (this.userProfile?.careerGoals) {
            recommendations.push({
                type: 'career',
                title: 'Alignement avec vos objectifs',
                description: 'Cet événement correspond à vos objectifs de carrière',
                priority: 'high'
            });
        }
        
        if (this.userProfile?.learningStyle) {
            recommendations.push({
                type: 'learning',
                title: 'Adapté à votre style d\'apprentissage',
                description: 'Le format de cet événement convient à votre style d\'apprentissage',
                priority: 'medium'
            });
        }
        
        return recommendations;
    }

    /**
     * Génère des recommandations basées sur l'historique
     */
    generateHistoryBasedRecommendations(eventData) {
        const recommendations = [];
        
        // Analyse des événements similaires dans l'historique
        const similarEvents = this.findSimilarEvents(eventData);
        
        if (similarEvents.length > 0) {
            recommendations.push({
                type: 'history',
                title: 'Similaire à vos événements précédents',
                description: `Vous avez apprécié ${similarEvents.length} événement(s) similaire(s)`,
                priority: 'medium'
            });
        }
        
        return recommendations;
    }

    /**
     * Génère des recommandations basées sur les tendances
     */
    generateTrendBasedRecommendations(eventData) {
        const recommendations = [];
        
        // Simulation de tendance
        const isTrending = Math.random() > 0.7;
        
        if (isTrending) {
            recommendations.push({
                type: 'trend',
                title: 'Tendance populaire',
                description: 'Cet événement est actuellement très populaire',
                priority: 'high'
            });
        }
        
        return recommendations;
    }

    /**
     * Génère des recommandations basées sur le réseau
     */
    generateNetworkBasedRecommendations(eventData) {
        const recommendations = [];
        
        // Simulation de participants du réseau
        const networkParticipants = Math.floor(Math.random() * 10);
        
        if (networkParticipants > 0) {
            recommendations.push({
                type: 'network',
                title: 'Opportunité de réseautage',
                description: `${networkParticipants} personne(s) de votre réseau y participe(nt)`,
                priority: 'medium'
            });
        }
        
        return recommendations;
    }

    /**
     * Trouve des événements similaires
     */
    findSimilarEvents(eventData) {
        return this.eventHistory.filter(event => 
            this.calculateEventSimilarity(event, eventData) > 0.7
        ).slice(0, 3);
    }

    /**
     * Calcule la similarité entre événements
     */
    calculateEventSimilarity(event1, event2) {
        const titleSimilarity = this.calculateTextSimilarity(event1.titre, event2.titre);
        const categorySimilarity = event1.category === event2.category ? 1 : 0;
        
        return (titleSimilarity * 0.7) + (categorySimilarity * 0.3);
    }

    /**
     * Calcule la similarité textuelle
     */
    calculateTextSimilarity(text1, text2) {
        const words1 = new Set(text1.toLowerCase().split(/\s+/));
        const words2 = new Set(text2.toLowerCase().split(/\s+/));
        
        const intersection = new Set([...words1].filter(x => words2.has(x)));
        const union = new Set([...words1, ...words2]);
        
        return intersection.size / union.size;
    }

    /**
     * Appelle l'enrichissement IA
     */
    async callAIEnrichment(eventData, analysis) {
        // Simulation d'appel API
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    summary: `Événement sur ${analysis.categorization.primary} avec une pertinence de ${Math.round(analysis.relevance.score * 100)}%`,
                    keyTopics: analysis.basic.title.slice(0, 3),
                    skillRequirements: ['Communication', 'Collaboration'],
                    careerImpact: 'Moyen',
                    networkingOpportunities: 'Élevé',
                    learningOutcomes: ['Connaissances nouvelles', 'Compétences pratiques']
                });
            }, 1000);
        });
    }

    /**
     * Génère un enrichissement de secours
     */
    generateFallbackEnrichment(analysis) {
        return {
            summary: 'Analyse basique de l\'événement',
            keyTopics: analysis.basic.title.slice(0, 2),
            skillRequirements: ['Participation active'],
            careerImpact: 'À déterminer',
            networkingOpportunities: 'Possible',
            learningOutcomes: ['Expérience']
        };
    }

    /**
     * Génère une analyse de secours
     */
    generateFallbackAnalysis(eventData) {
        return {
            basic: this.performBasicAnalysis(eventData),
            categorization: this.categorizeEvent(eventData),
            relevance: { score: 0.5, reasons: ['Analyse de secours'] },
            recommendations: [],
            insights: [],
            prediction: { predictedEngagement: 0.5, confidence: 0.3 },
            ai: this.generateFallbackEnrichment({}),
            timestamp: new Date().toISOString(),
            confidence: 0.3
        };
    }

    /**
     * Charge le profil utilisateur
     */
    async loadUserProfile() {
        try {
            // Simulation du chargement du profil
            this.userProfile = {
                interests: ['technology', 'business', 'networking'],
                skills: ['programming', 'marketing', 'communication'],
                careerGoals: ['software development', 'entrepreneurship'],
                availability: { weekdays: true, weekends: true },
                location: 'Tunis',
                learningStyle: 'visual'
            };
        } catch (error) {
            console.error('Error loading user profile:', error);
            this.userProfile = null;
        }
    }

    /**
     * Charge l'historique des événements
     */
    loadEventHistory() {
        try {
            // Simulation du chargement de l'historique
            this.eventHistory = [];
        } catch (error) {
            console.error('Error loading event history:', error);
            this.eventHistory = [];
        }
    }

    /**
     * Configure les écouteurs d'événements
     */
    setupEventListeners() {
        document.addEventListener('event:analyze', async (event) => {
            const eventData = event.detail;
            const analysis = await this.analyzeEvent(eventData);
            
            document.dispatchEvent(new CustomEvent('event:analyzed', {
                detail: analysis
            }));
        });
    }
}

// Export de la classe
window.AIEventAnalyzer = AIEventAnalyzer;

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    window.eventAnalyzer = new AIEventAnalyzer();
});
