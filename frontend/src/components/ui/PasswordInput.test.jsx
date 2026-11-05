import { fireEvent, render, screen } from '@testing-library/react'
import { expect, it } from 'vitest'
import { PasswordInput } from './index'

it('affiche puis masque le mot de passe', () => {
  render(<PasswordInput aria-label="Mot de passe" defaultValue="secret" />)
  const input = screen.getByLabelText('Mot de passe')
  expect(input).toHaveAttribute('type', 'password')

  fireEvent.click(screen.getByRole('button', { name: 'Afficher le mot de passe' }))
  expect(input).toHaveAttribute('type', 'text')

  fireEvent.click(screen.getByRole('button', { name: 'Masquer le mot de passe' }))
  expect(input).toHaveAttribute('type', 'password')
})
