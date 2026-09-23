<?php
namespace PuneMirror\Contracts;

interface EditorialRepository
{
    public function saveAction(array $action): array;
    public function actionsForContent(string $contentId): array;
    public function allActions(): array;
}
