import client from './client'

const unwrap = (p) => p.then((r) => r.data)

/** Télécharge un fichier ; une erreur reçue en blob est relue en JSON pour apiError(). */
const fetchBlob = async (url, params) => {
  try {
    return (await client.get(url, { params, responseType: 'blob' })).data
  } catch (error) {
    const body = error.response?.data
    if (body instanceof Blob) {
      try {
        error.response.data = JSON.parse(await body.text())
      } catch {
        error.response.data = {}
      }
    }
    throw error
  }
}

export const auth = {
  login: (payload) => unwrap(client.post('/login', payload)),
  logout: () => client.post('/logout'),
  me: () => unwrap(client.get('/me')),
}

export const members = {
  list: (params) => unwrap(client.get('/members', { params })),
  get: (id) => unwrap(client.get(`/members/${id}`)).then((r) => r.data),
  byQr: (token) => unwrap(client.get(`/members/by-qr/${token}`)).then((r) => r.data),
  create: (formData) => unwrap(client.post('/members', formData, { headers: { 'Content-Type': 'multipart/form-data' } })).then((r) => r.data),
  update: (id, formData) => {
    formData.append('_method', 'PUT')
    return unwrap(client.post(`/members/${id}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })).then((r) => r.data)
  },
  remove: (id) => client.delete(`/members/${id}`),
  updateStatus: (id, payload) => unwrap(client.patch(`/members/${id}/status`, payload)).then((r) => r.data),
  validate: (id) => unwrap(client.post(`/members/${id}/validate`)).then((r) => r.data),
  subscriptions: (id) => unwrap(client.get(`/members/${id}/subscriptions`)).then((r) => r.data),
  suggestedStart: (id) => unwrap(client.get(`/members/${id}/subscriptions/suggested-start`)),
  attendances: (id, params) => unwrap(client.get(`/members/${id}/attendances`, { params })),
  payments: (id, params) => unwrap(client.get(`/members/${id}/payments`, { params })),
  outstanding: (id) => unwrap(client.get(`/members/${id}/outstanding`)),
  reminders: (id) => unwrap(client.get(`/members/${id}/reminders`)).then((r) => r.data),
  programs: (id, params) => unwrap(client.get(`/members/${id}/programs`, { params })),
  grantAppAccess: (id) => unwrap(client.post(`/members/${id}/app-access`)),
  revokeAppAccess: (id) => client.delete(`/members/${id}/app-access`),
}

export const kiosk = {
  token: () => unwrap(client.get('/kiosk/token')),
}

export const plans = {
  list: (params) => unwrap(client.get('/subscription-plans', { params })).then((r) => r.data),
  create: (payload) => unwrap(client.post('/subscription-plans', payload)).then((r) => r.data),
  update: (id, payload) => unwrap(client.put(`/subscription-plans/${id}`, payload)).then((r) => r.data),
  remove: (id) => client.delete(`/subscription-plans/${id}`),
}

export const subscriptions = {
  create: (payload) => unwrap(client.post('/subscriptions', payload)).then((r) => r.data),
  cancel: (id, reason) => unwrap(client.patch(`/subscriptions/${id}/cancel`, { reason })).then((r) => r.data),
}

export const attendances = {
  check: (params) => unwrap(client.get('/attendances/check', { params })),
  create: (payload) => unwrap(client.post('/attendances', payload)),
  list: (params) => unwrap(client.get('/attendances', { params })),
  remove: (id) => client.delete(`/attendances/${id}`),
}

export const payments = {
  list: (params) => unwrap(client.get('/payments', { params })),
  create: (payload) => unwrap(client.post('/payments', payload)).then((r) => r.data),
  batch: (payload) => unwrap(client.post('/payments/batch', payload)).then((r) => r.data),
  cancel: (id, reason) => unwrap(client.patch(`/payments/${id}/cancel`, { reason })).then((r) => r.data),
  receipt: (id) => unwrap(client.get(`/payments/${id}/receipt`, { params: { format: 'json' } })).then((r) => r.data),
  receiptPdf: (id) => fetchBlob(`/payments/${id}/receipt`, { format: 'pdf' }),
}

export const recovery = {
  latePayments: (params) => unwrap(client.get('/late-payments', { params })),
  absences: (params) => unwrap(client.get('/absences', { params })),
  reminderPreview: (params) => unwrap(client.get('/reminders/preview', { params })),
  sendReminder: (payload) => unwrap(client.post('/reminders', payload)).then((r) => r.data),
}

export const cashbook = {
  report: (params) => unwrap(client.get('/cashbook', { params })),
  exportCsv: (params) => fetchBlob('/cashbook/export', params),
  categories: (params) => unwrap(client.get('/expense-categories', { params })).then((r) => r.data),
  createCategory: (payload) => unwrap(client.post('/expense-categories', payload)).then((r) => r.data),
  updateCategory: (id, payload) => unwrap(client.put(`/expense-categories/${id}`, payload)).then((r) => r.data),
  expenses: (params) => unwrap(client.get('/expenses', { params })),
  createExpense: (formData) => unwrap(client.post('/expenses', formData, { headers: { 'Content-Type': 'multipart/form-data' } })).then((r) => r.data),
  cancelExpense: (id, reason) => unwrap(client.patch(`/expenses/${id}/cancel`, { reason })).then((r) => r.data),
}

export const dashboard = {
  get: (params) => unwrap(client.get('/dashboard', { params })),
}

export const settings = {
  get: () => unwrap(client.get('/settings')),
  update: (payload) => unwrap(client.put('/settings', payload)),
}

export const users = {
  list: () => unwrap(client.get('/users')).then((r) => r.data),
  create: (payload) => unwrap(client.post('/users', payload)).then((r) => r.data),
  update: (id, payload) => unwrap(client.put(`/users/${id}`, payload)).then((r) => r.data),
  deactivate: (id) => client.delete(`/users/${id}`),
}

export const programs = {
  list: (params) => unwrap(client.get('/programs', { params })),
  get: (id) => unwrap(client.get(`/programs/${id}`)).then((r) => r.data),
  create: (memberId, payload) => unwrap(client.post(`/members/${memberId}/programs`, payload)).then((r) => r.data),
  update: (id, payload) => unwrap(client.put(`/programs/${id}`, payload)).then((r) => r.data),
  approve: (id, note) => unwrap(client.patch(`/programs/${id}/approve`, { note })).then((r) => r.data),
  decline: (id, note) => unwrap(client.patch(`/programs/${id}/decline`, { note })).then((r) => r.data),
  archive: (id) => unwrap(client.patch(`/programs/${id}/archive`)).then((r) => r.data),
}
