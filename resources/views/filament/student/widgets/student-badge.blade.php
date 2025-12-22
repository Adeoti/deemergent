<x-filament::widget>
    <div class="student-badge-section p-0">
        <!-- Student Badge -->
        <div class="student-badge-container m-0">

            <!-- Student Passport Column -->
            <div class="passport-column">
                @php
                    $student = auth()->user(); // Get the currently authenticated user
                @endphp

                <!-- Passport Image -->
                <div class="passport-img-container">
                    @if($student->passport)
                        <img src="{{ Storage::url($student->passport) }}" alt="Student Passport" class="passport-img">
                    @else
                        <img src="{{ asset('images/default-passport.jpg') }}" alt="Default Passport" class="passport-img">
                    @endif
                </div>
                <h2 class="student-name">{{ $student->name }}</h2>
                <p class="student-class">{{ $student->class->name }}</p>
            </div>

            <!-- Student Details Column -->
            <div class="details-column">
                <h3 class="section-title details-title">Icon Information</h3>
                <p class="detail-item"><span class="bold">Roll Number:</span> {{ $student->student->roll_number ?? 'N/A' }}</p>
                <p class="detail-item"><span class="bold">Parent:</span> {{ $student->student->guardian_name ?? 'N/A' }}</p>
                <p class="detail-item"><span class="bold">Parent Contact:</span> {{ $student->student->parent_contact ?? 'N/A' }}</p>
            </div>

            <!-- Student Contact Column -->
            <div class="contact-column">
                <h3 class="section-title contact-title">Contact Information</h3>
                <p class="contact-item"><span class="bold">Email:</span> {{ $student->email }}</p>
                <p class="contact-item"><span class="bold">Phone:</span> {{ $student->student->guardian_phone ?? 'N/A' }}</p>
                <p class="contact-item"><span class="bold">Address:</span> {{ $student->student->address ?? 'N/A' }}</p>
            </div>

        </div>
    </div>
</x-filament::widget>
@assets
<style>
    /* General styles */
.student-badge-section {
    background-color: transparent;
    padding: 0px !important;
    border-radius: 10px;
}

/* Container for the badge layout */
.student-badge-container {
    display: grid;
    grid-template-columns: 1fr 2fr 2fr;
    gap: 20px;
    background-color: #f5f5f5; /* Light gray background */
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    padding: 30px;
    color: #000000; /* Black text color */
}

/* Passport Column */
.passport-column {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.passport-img-container {
    text-align: center;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 15px;
    border: 4px solid #d3d3d3; /* Light gray border */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.passport-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.student-name {
    font-size: 1.3rem;
    font-weight: bold;
    margin-bottom: 5px;
    color: #000000; /* Black text */
}

.student-class {
    font-size: 1.2rem;
    color: #333333; /* Dark gray text */
}

/* Details and Contact Column */
.details-column,
.contact-column {
    background-color: #ffffff; /* White background for contrast */
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid #e0e0e0;
}

.section-title {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 15px;
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    color: #ffffff; /* White text for titles */
}

.details-title {
    background-color: #4a5568; /* Dark gray for details title */
}

.contact-title {
    background-color: #2d3748; /* Slightly darker gray for contact title */
}

.detail-item,
.contact-item {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: #000000; /* Black text */
}

.bold {
    font-weight: bold;
    color: #2d3748; /* Dark color for bold text */
}

/* Responsive Design */
@media (max-width: 768px) {
    .student-badge-container {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .passport-img-container {
        width: 100px;
        height: 100px;
    }

    .student-name {
        font-size: 1.6rem;
    }

    .student-class {
        font-size: 1rem;
    }

    .section-title {
        font-size: 1.2rem;
    }

    .detail-item,
    .contact-item {
        font-size: 1rem;
    }
}
</style>
@endassets