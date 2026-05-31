<?php

namespace App\Livewire;

use App\Models\Lead;
use App\Models\Task;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TaskList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $leadFilter = '';

    public string $sortField = 'due_date';

    public string $sortDirection = 'asc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    public function deleteTask(int $id): void
    {
        Task::findOrFail($id)->delete();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $tasks = Task::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->leadFilter, fn ($q) => $q->whereHas('lead', fn ($l) => $l->where('id', $this->leadFilter)))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        $leads = Lead::all();

        return view('livewire.task-list', ['tasks' => $tasks, 'leads' => $leads]);
    }
}
