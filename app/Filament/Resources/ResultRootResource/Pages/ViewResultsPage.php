<?php
namespace App\Filament\Resources\ResultRootResource\Pages;

use App\Models\ResultUpload;
use App\Filament\Resources\ResultRootResource;
use App\Models\ResultRoot;
use App\Models\TeacherRemark;
use Filament\Resources\Pages\Page;

class ViewResultsPage extends Page
{
    protected static string $resource = ResultRootResource::class;

    protected static string $view = 'filament.resources.result-root-resource.pages.view-results-page';

    public $resultUploads;
    public ResultRoot $record;
    public $schoolDetails;
    public $teacherRemarks; // Add this

    public function mount(ResultRoot $record)
    {
        $this->schoolDetails = getSchoolDetails();
        $this->record = $record;
        
        // Fetch result uploads for the specific result root record
        $this->resultUploads = ResultUpload::where('result_root_id', $record->id)->get();
        
        // Load teacher remarks for this result root
        $this->teacherRemarks = TeacherRemark::where('result_root_id', $record->id)
            ->get()
            ->keyBy('student_id');
    }

    public function getTitle(): string
    {
        return '';
    }
}