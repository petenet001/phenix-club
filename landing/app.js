import { CONFIG } from './config.js'
import { renderIcons } from './icons.js'

const $ = (sel) => document.querySelector(sel)
const fcfa = (n) => `${Number(n || 0).toLocaleString('fr-FR').replace(/ /g, ' ')} FCFA`
const escape = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c])

renderIcons()
$('#year').textContent = new Date().getFullYear()

/* Menu sur téléphone */
const burger = $('#burger')
const nav = $('#nav')
burger.addEventListener('click', () => {
  const open = nav.classList.toggle('is-open')
  burger.setAttribute('aria-expanded', String(open))
  burger.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu')
})
nav.addEventListener('click', (e) => {
  if (e.target.closest('a')) {
    nav.classList.remove('is-open')
    burger.setAttribute('aria-expanded', 'false')
  }
})

/* Apparition des blocs au défilement */
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add('is-visible')
      observer.unobserve(entry.target)
    }
  })
}, { rootMargin: '0px 0px -8% 0px' })
document.querySelectorAll('.reveal').forEach((el, i) => {
  el.style.transitionDelay = `${(i % 3) * 60}ms`
  observer.observe(el)
})

/* Horaires et réseaux sociaux (config.js) */
if (CONFIG.hours.length) {
  $('#hours').innerHTML = CONFIG.hours.map((h) => `${escape(h.days)} : ${escape(h.hours)}`).join('<br>')
}
const socialLabels = { facebook: 'Facebook', instagram: 'Instagram', tiktok: 'TikTok' }
$('#social').innerHTML = Object.entries(CONFIG.social)
  .filter(([, url]) => url)
  .map(([key, url]) => `<a href="${escape(url)}" target="_blank" rel="noopener">${socialLabels[key]}</a>`)
  .join('')

/* Données du club : coordonnées, formules, ouverture des inscriptions */
function describePlan(p) {
  if (p.description) return p.description
  if (p.sessions_count) return `${p.sessions_count} séances à utiliser sous ${p.duration_days} jours`
  return p.duration_days === 1 ? 'Accès pour une journée' : `${p.duration_days} jours en accès illimité`
}

function renderPlans(plans) {
  const container = $('#plans')
  if (!plans.length) {
    container.innerHTML = '<p class="muted center">Nos formules vous sont présentées à l\'accueil.</p>'
    return
  }
  // Mise en avant de la formule mensuelle (la plus courante) si elle existe
  const featured = plans.find((p) => p.duration_days === 30 && !p.sessions_count)
  container.innerHTML = plans.map((p) => `
    <article class="plan reveal is-visible ${p === featured ? 'plan--featured' : ''}">
      ${p === featured ? '<span class="plan__badge">Le plus choisi</span>' : ''}
      <h3>${escape(p.name)}</h3>
      <p class="plan__price">${fcfa(p.price).replace(' FCFA', '')} <small>FCFA</small></p>
      <p>${escape(describePlan(p))}</p>
    </article>`).join('')

  $('#plan-select').insertAdjacentHTML('beforeend', plans.map((p) => `<option value="${p.id}">${escape(p.name)} - ${fcfa(p.price)}</option>`).join(''))
}

function renderClub(club, fee) {
  document.querySelectorAll('[data-club="address"]').forEach((el) => { el.textContent = club.address || 'Brazzaville' })
  if (club.phone) {
    const link = document.querySelector('[data-club="phone"]')
    link.textContent = club.phone
    link.href = `tel:${club.phone.replace(/\s/g, '')}`
    $('#info-phone').hidden = false
  }
  if (club.whatsapp_url) {
    $('#whatsapp-link').href = club.whatsapp_url
    $('#info-whatsapp').hidden = false
  }
  if (fee > 0) {
    $('#fee-note').textContent = `Frais d'inscription unique : ${fcfa(fee)}. Paiement à l'accueil en espèces, Mobile Money ou virement.`
  }
}

async function loadClub() {
  try {
    const res = await fetch(`${CONFIG.apiUrl}/public/club`, { headers: { Accept: 'application/json' } })
    if (!res.ok) throw new Error(res.status)
    const data = await res.json()
    renderClub(data.club, data.registration_fee)
    renderPlans(data.plans)
    if (data.online_registration_enabled) $('#join-form').hidden = false
    else $('#join-closed').hidden = false
  } catch {
    // API injoignable : le site reste lisible, l'inscription se fait sur place
    $('#plans').innerHTML = '<p class="muted center">Nos formules vous sont présentées à l\'accueil.</p>'
    $('#join-closed').hidden = false
  }
}

/* Demande d'adhésion */
const form = $('#join-form')
form.addEventListener('submit', async (e) => {
  e.preventDefault()
  const error = $('#join-error')
  error.hidden = true
  form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'))

  const missing = [...form.querySelectorAll('[required]')].filter((el) => !el.value.trim())
  if (missing.length) {
    missing.forEach((el) => el.setAttribute('aria-invalid', 'true'))
    missing[0].focus()
    error.textContent = 'Merci de renseigner votre prénom, votre nom et votre téléphone.'
    error.hidden = false
    return
  }

  const payload = Object.fromEntries([...new FormData(form)].filter(([, v]) => String(v).trim() !== ''))
  const button = form.querySelector('button[type="submit"]')
  button.disabled = true
  button.textContent = 'Envoi…'

  try {
    const res = await fetch(`${CONFIG.apiUrl}/public/registrations`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) {
      const fields = data.errors || {}
      Object.keys(fields).forEach((name) => form.elements[name]?.setAttribute('aria-invalid', 'true'))
      const first = Object.values(fields)[0]?.[0]
      throw new Error(res.status === 429 ? 'Trop de demandes envoyées, réessayez dans un moment.' : (first || data.message || 'Envoi impossible.'))
    }
    form.hidden = true
    const success = $('#join-success')
    success.innerHTML = `<h3>Demande enregistrée</h3><p>${escape(data.message)}</p><p>Votre numéro d'adhérent : <strong>${escape(data.code)}</strong></p>`
    success.hidden = false
  } catch (err) {
    error.textContent = err.message === 'Failed to fetch' ? 'Impossible de joindre le club pour le moment. Réessayez plus tard.' : err.message
    error.hidden = false
  } finally {
    button.disabled = false
    button.textContent = 'Envoyer ma demande'
  }
})

loadClub()
