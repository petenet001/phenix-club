/**
 * Réglages du site vitrine, à adapter avant la mise en ligne.
 *
 * Les formules, tarifs, frais d'inscription, nom, adresse et téléphone du club viennent de l'API
 * (écran Paramètres du bureau) : rien à recopier ici.
 */
export const CONFIG = {
  // Adresse de l'API Laravel (en production : https://api.votre-domaine/api)
  apiUrl: 'http://localhost:8000/api',

  // Horaires d'ouverture. Laisser vide tant qu'ils ne sont pas confirmés : le site affiche alors
  // « Renseignez-vous à l'accueil ». Exemple : [{ days: 'Lundi - Vendredi', hours: '6h - 21h' }]
  hours: [],

  // Liens des réseaux sociaux (vides = masqués)
  social: {
    facebook: '',
    instagram: '',
    tiktok: '',
  },
}
