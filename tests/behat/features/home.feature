Feature: Prueba de la página de inicio
  In order to ensure the site is working correctly
  As a site visitor
  I want to see the homepage

  Scenario: Ver la página de inicio
    Given I am on the homepage
    Then I should see "Bienvenido a mi sitio web"