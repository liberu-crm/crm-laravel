<?php

namespace App\Http\Livewire;

use App\Models\Lead;
use App\Models\Task;
use Livewire\Component;
use Livewire\WithPagination;

class TaskList extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $leadFilter = '';

    public $sortField = 'due_date';

    public $sortDirection = 'asc';

    protected $queryString = ['search', 'status', 'leadFilter'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy($field): void
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
            ->when($this->search, function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->when($this->status, function ($query): void {
                $query->where('status', $this->status);
            })
            ->when($this->leadFilter, function ($query): void {
                $query->whereHas('lead', function ($leadQuery): void {
                    $leadQuery->where('id', $this->leadFilter);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        $leads = Lead::all();

        return view('livewire.task-list', [
            'tasks' => $tasks,
            'leads' => $leads,
        ]);
    }
}
