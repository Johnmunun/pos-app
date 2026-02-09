<?php

namespace Src\Domains\GlobalSearch\ValueObjects;

/**
 * Value Object GlobalSearchItem
 *
 * Représente un élément de recherche dans le système.
 * Aucune dépendance vers Laravel ou l'infrastructure.
 *
 * @package Domains\GlobalSearch\ValueObjects
 */
final class GlobalSearchItem
{
    /**
     * @param string $label Label affiché à l'utilisateur
     * @param string $description Description optionnelle
     * @param string $routeName Nom de la route Laravel
     * @param string|null $requiredPermission Permission requise pour accéder à cette page
     * @param string $module Module auquel appartient cette page (Access, Pharmacy, Sales, etc.)
     * @param string|null $icon Nom de l'icône Lucide (optionnel)
     */
    public function __construct(
        private readonly string $label,
        private readonly string $description,
        private readonly string $routeName,
        private readonly ?string $requiredPermission,
        private readonly string $module,
        private readonly ?string $icon = null
    ) {
        if (empty(trim($label))) {
            throw new \InvalidArgumentException('Label cannot be empty');
        }

        if (empty(trim($routeName))) {
            throw new \InvalidArgumentException('Route name cannot be empty');
        }

        if (empty(trim($module))) {
            throw new \InvalidArgumentException('Module cannot be empty');
        }
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getRequiredPermission(): ?string
    {
        return $this->requiredPermission;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Vérifie si le terme de recherche correspond à cet item
     * Recherche insensible à la casse et partielle
     *
     * @param string $searchTerm
     * @return bool
     */
    public function matches(string $searchTerm): bool
    {
        $searchTerm = mb_strtolower(trim($searchTerm));
        
        if (empty($searchTerm)) {
            return false;
        }

        $labelLower = mb_strtolower($this->label);
        $descriptionLower = mb_strtolower($this->description);
        $moduleLower = mb_strtolower($this->module);

        return str_contains($labelLower, $searchTerm)
            || str_contains($descriptionLower, $searchTerm)
            || str_contains($moduleLower, $searchTerm);
    }

    /**
     * Convertit l'item en tableau pour la sérialisation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'description' => $this->description,
            'route_name' => $this->routeName,
            'required_permission' => $this->requiredPermission,
            'module' => $this->module,
            'icon' => $this->icon,
        ];
    }
}
