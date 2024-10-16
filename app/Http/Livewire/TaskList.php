<?php

namespace App\Http\Livewire;

use App\Models\Task;
use App\Models\Lead;
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

    protected $queryString = ['search', 'status', 'leadFilter', 'sortField', 'sortDirection'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    public function render()
    {
        $tasks = Task::query()
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('lead', function ($leadQuery) {
                            $leadQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->leadFilter, function ($query) {
                $query->whereHas('lead', function ($leadQuery) {
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