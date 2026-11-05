import { createBrowserRouter, Navigate } from 'react-router-dom'
import AppLayout from './components/layout/AppLayout'
import ErrorBoundary from './components/ErrorBoundary'
import { RequireAuth, RequireRole } from './components/layout/RequireAuth'
import LoginPage from './pages/LoginPage'
import DashboardPage from './pages/DashboardPage'
import CheckInPage from './pages/CheckInPage'
import MembersPage from './pages/MembersPage'
import MemberFormPage from './pages/MemberFormPage'
import MemberDetailPage from './pages/MemberDetailPage'
import PaymentsPage from './pages/PaymentsPage'
import LatePaymentsPage from './pages/LatePaymentsPage'
import AbsencesPage from './pages/AbsencesPage'
import CashbookPage from './pages/CashbookPage'
import ProgramsPage from './pages/ProgramsPage'
import SettingsPage from './pages/SettingsPage'

function Home() {
  return (
    <RequireRole roles={['manager']}>
      <DashboardPage />
    </RequireRole>
  )
}

export const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  {
    path: '/',
    element: (
      <RequireAuth>
        <AppLayout />
      </RequireAuth>
    ),
    errorElement: <ErrorBoundary />,
    children: [
      { index: true, element: <Home /> },
      { path: 'pointage', element: <CheckInPage /> },
      { path: 'adherents', element: <MembersPage /> },
      { path: 'adherents/nouveau', element: <MemberFormPage /> },
      { path: 'adherents/:id', element: <MemberDetailPage /> },
      { path: 'adherents/:id/modifier', element: <MemberFormPage /> },
      { path: 'paiements', element: <PaymentsPage /> },
      { path: 'retards', element: <LatePaymentsPage /> },
      { path: 'absences', element: <AbsencesPage /> },
      { path: 'caisse', element: <CashbookPage /> },
      { path: 'programmes', element: <RequireRole roles={['manager', 'coach']}><ProgramsPage /></RequireRole> },
      { path: 'parametres', element: <RequireRole roles={['manager']}><SettingsPage /></RequireRole> },
      { path: '*', element: <Navigate to="/" replace /> },
    ],
  },
])
