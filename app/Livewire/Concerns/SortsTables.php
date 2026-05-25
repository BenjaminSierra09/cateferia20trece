<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

trait SortsTables
{
    #[Url(as: 'sort', keep: true)]
    public string $sortBy = '';

    #[Url(as: 'direction', keep: true)]
    public string $sortDirection = 'asc';

    public function sort(string $column): void
    {
        if (! array_key_exists($column, $this->sortableColumns())) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetSortedPagination();
    }

    protected function applySorting(Builder $query): Builder
    {
        $column = $this->sortableColumns()[$this->sortBy] ?? null;

        if ($column === null) {
            return $query;
        }

        return $query->orderBy($column, $this->sortDirection === 'desc' ? 'desc' : 'asc');
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [];
    }

    protected function resetSortedPagination(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
