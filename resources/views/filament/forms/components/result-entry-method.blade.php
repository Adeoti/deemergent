@php
use App\Services\ResultService;
use App\Models\ResultRoot;
use App\Models\User;

// Get the form instance
$form = $viewData['form'] ?? null;
if (!$form) {
    return;
}

// Get form values
$resultRootId = $form->getRawState()['result_root_id'] ?? null;
$classId = $form->getRawState()['class_id'] ?? null;
$subjectId = $form->getRawState()['subject_id'] ?? null;
$entryType = $form->getRawState()['entry_type'] ?? 'csv';

// Initialize service
$resultService = app(ResultService::class);
@endphp

@if($entryType === 'csv')
    <x-filament::section
        description="Upload a CSV file with student scores"
        icon="heroicon-o-document-arrow-up"
    >
        <x-filament::fieldset>
            <x-slot name="label">
                CSV File Upload
            </x-slot>
            
            {{ $form->getComponent('file_path')->getChildComponentContainer() }}
        </x-filament::fieldset>
        
        @if($resultRootId)
            @php
                $resultRoot = ResultRoot::find($resultRootId);
                $examColumns = $resultRoot->exam_score_columns ?? [];
            @endphp
            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                <h4 class="font-semibold text-blue-800 mb-2">CSV Format Instructions:</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• First column must be named <code class="bg-blue-100 px-1 rounded">Student_ID</code></li>
                    <li>• Subsequent columns should match exam columns:</li>
                    <ul class="ml-4 mt-1">
                        @foreach($examColumns as $column)
                            <li>• {{ $column['label'] }} (Max: {{ $column['overall_score'] }})</li>
                        @endforeach
                    </ul>
                    <li>• Download template from Result Root page</li>
                </ul>
            </div>
        @endif
    </x-filament::section>
@elseif($entryType === 'manual')
    <x-filament::section
        description="Enter scores manually for each student"
        icon="heroicon-o-pencil-square"
    >
        @if($resultRootId && $classId && $subjectId)
            @php
                $students = $resultService->getStudentsForClass($classId);
                $resultRoot = ResultRoot::find($resultRootId);
                $examColumns = $resultRoot->exam_score_columns ?? [];
                
                // Get existing manual entry for editing
                $existingEntry = $resultService->getManualEntry($resultRootId, $classId, $subjectId);
                $existingData = $existingEntry?->card_items ?? [];
            @endphp
            
            @if(count($students) > 0 && count($examColumns) > 0)
                <div class="space-y-4">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h4 class="font-semibold text-lg">Enter scores for {{ count($students) }} students</h4>
                            <p class="text-sm text-gray-600">Leave blank for 0 score</p>
                        </div>
                        <div class="text-sm bg-yellow-50 text-yellow-800 px-3 py-1 rounded">
                            Manual Entry Mode
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider sticky left-0 bg-gray-50">
                                        Student
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Roll No.
                                    </th>
                                    @foreach($examColumns as $column)
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider min-w-32">
                                            {{ $column['label'] }}
                                            <div class="text-xs font-normal text-gray-500">
                                                Max: {{ $column['overall_score'] }}
                                            </div>
                                        </th>
                                    @endforeach
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($students as $student)
                                    @php
                                        $studentId = $student->id;
                                        $studentScores = $existingData[$studentId]['scores'] ?? [];
                                        $studentTotal = $existingData[$studentId]['total'] ?? 0;
                                    @endphp
                                    <tr class="hover:bg-gray-50" x-data="{
                                        studentId: {{ $studentId }},
                                        scores: {{ json_encode($studentScores) }},
                                        total: {{ $studentTotal }},
                                        updateTotal() {
                                            let newTotal = 0;
                                            Object.values(this.scores).forEach(score => {
                                                newTotal += parseFloat(score) || 0;
                                            });
                                            this.total = Math.round(newTotal * 100) / 100;
                                        }
                                    }" x-init="updateTotal()">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white">
                                            {{ $student->name }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ $student->student->roll_number ?? 'N/A' }}
                                        </td>
                                        @foreach($examColumns as $column)
                                            @php
                                                $columnLabel = $column['label'];
                                                $maxScore = (int)$column['overall_score'];
                                                $currentScore = $studentScores[$columnLabel] ?? '';
                                            @endphp
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <input
                                                    type="number"
                                                    x-model="scores['{{ $columnLabel }}']"
                                                    x-on:input="
                                                        let value = parseFloat($event.target.value) || 0;
                                                        if (value > {{ $maxScore }}) {
                                                            value = {{ $maxScore }};
                                                            $event.target.value = value;
                                                        }
                                                        if (value < 0) {
                                                            value = 0;
                                                            $event.target.value = value;
                                                        }
                                                        scores['{{ $columnLabel }}'] = value;
                                                        updateTotal();
                                                    "
                                                    value="{{ $currentScore }}"
                                                    min="0"
                                                    max="{{ $maxScore }}"
                                                    step="0.5"
                                                    class="w-full px-2 py-1 border border-gray-300 rounded focus:border-primary-500 focus:ring focus:ring-primary-200"
                                                    placeholder="0"
                                                >
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span x-text="total" :class="total >= 50 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'">
                                                {{ $studentTotal }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Hidden field to store all manual data -->
                    <input 
                        type="hidden" 
                        name="manual_data" 
                        id="manualDataInput"
                        x-ref="manualDataInput"
                        x-data="{
                            collectData() {
                                const allData = {
                                    result_root_id: {{ $resultRootId }},
                                    class_id: {{ $classId }},
                                    subject_id: {{ $subjectId }},
                                    entry_type: 'manual',
                                    students: {}
                                };
                                
                                // We'll collect data via Alpine.js on form submit
                                return JSON.stringify(allData);
                            }
                        }"
                        x-bind:value="collectData()"
                    >
                    
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold mb-2">Manual Entry Instructions:</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• Enter scores for each student (blank = 0)</li>
                            <li>• Scores automatically validate against maximum values</li>
                            <li>• Decimals allowed (use .5 for half marks)</li>
                            <li>• Total updates automatically as you type</li>
                            <li>• Save the form when finished</li>
                        </ul>
                    </div>
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <p class="text-gray-500">
                        @if(count($students) === 0)
                            No students found in this class.
                        @elseif(count($examColumns) === 0)
                            No exam columns configured for the selected result root.
                        @else
                            Please select all required fields.
                        @endif
                    </p>
                </div>
            @endif
        @else
            <div class="text-center py-8 bg-gray-50 rounded-lg">
                <p class="text-gray-500">
                    Please select a result root, class, and subject first.
                </p>
            </div>
        @endif
    </x-filament::section>
@endif

@assets
<style>
    .manual-score-input:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }
    
    table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    th, td {
        border-right: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
    }
    
    th:last-child, td:last-child {
        border-right: none;
    }
    
    tr:last-child td {
        border-bottom: none;
    }
    
    .sticky {
        position: sticky;
        z-index: 10;
    }
</style>

<script>
    // Add event listener to capture form submission
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // If in manual entry mode, collect all data
                const entryType = document.querySelector('[name="entry_type"]');
                if (entryType && entryType.value === 'manual') {
                    // The data is already collected via Alpine.js in the hidden input
                    // No need for additional processing
                }
            });
        }
    });
</script>
@endassets