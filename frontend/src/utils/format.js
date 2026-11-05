import dayjs from 'dayjs'
import 'dayjs/locale/fr'

dayjs.locale('fr')

/** Déclenche le téléchargement d'un Blob sous le nom donné. */
export function saveBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

export function formatFcfa(amount) {
  const n = Number(amount || 0)
  return `${n.toLocaleString('fr-FR').replace(/ /g, ' ')} FCFA`
}

export function formatDate(value) {
  if (!value) return '-'
  return dayjs(value).format('DD/MM/YYYY')
}

export function formatDateTime(value) {
  if (!value) return '-'
  return dayjs(value).format('DD/MM/YYYY HH:mm')
}

export function formatTime(value) {
  if (!value) return '-'
  return dayjs(value).format('HH:mm')
}

export function today() {
  return dayjs().format('YYYY-MM-DD')
}

export function startOfMonth() {
  return dayjs().startOf('month').format('YYYY-MM-DD')
}

export function endOfMonth() {
  return dayjs().endOf('month').format('YYYY-MM-DD')
}

export const METHOD_LABELS = { cash: 'Espèces', mobile_money: 'Mobile Money', bank_transfer: 'Virement' }
export const TYPE_LABELS = { registration: "Frais d'inscription", subscription: 'Abonnement', other: 'Autre' }
export const STATUS_LABELS = { pending: 'En attente', active: 'Actif', inactive: 'Inactif', suspended: 'Suspendu' }
export const STATUS_COLORS = {
  pending: 'bg-amber-100 text-amber-800',
  active: 'bg-emerald-100 text-emerald-800',
  inactive: 'bg-slate-200 text-slate-700',
  suspended: 'bg-red-100 text-red-800',
}
export const SUB_STATUS_LABELS = { pending: 'À venir', active: 'En cours', expired: 'Expiré', cancelled: 'Annulé' }
export const DAYS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']
