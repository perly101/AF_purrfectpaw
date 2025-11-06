@php
    use App\Models\ClinicInfo;
    $clinic = $clinic ?? ClinicInfo::where('user_id', auth()->id())->first();
@endphp

<x-app-layout>
    {{-- Include mobile navigation (only visible on mobile) --}}
    @include('clinic.components.mobile-nav')

    <div class="flex flex-col md:flex-row min-h-screen bg-gray-100">
        {{-- Sidebar (hidden on mobile) --}}
        <div class="md:block hidden">
            @include('clinic.components.sidebar')
        </div>

        {{-- Main Content --}}
        <div class="flex-1 p-4 md:p-6 md:ml-64 w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div class="mb-4 md:mb-0">
                    <h2 class="text-xl md:text-2xl font-semibold text-gray-800">Manage Appointment Form Fields</h2>
                    <p class="text-gray-500 text-sm mt-1">Customize the fields that appear on your appointment form</p>
                </div>
                
                <div class="flex space-x-2">
                    <a href="{{ route('clinic.availability.index') }}" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm flex items-center transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Manage Availability</span>
                    </a>
                    <a href="{{ route('appointments.preview', $clinic->id) }}" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Preview Form</span>
                    </a>
                </div>
            </div>
            
            <div class="bg-white shadow-lg rounded-xl p-4 sm:p-6 md:p-8 border border-gray-200">
                    @if(session('success'))
                        <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-400 text-green-700 text-sm">{{ session('success') }}</div>
                    @endif

                    @if(session('success'))
                        <div class="mb-4 text-green-600 text-sm">{{ session('success') }}</div>
                    @endif

                    {{-- Create Field Form --}}
                    <form action="{{ route('clinic.fields.store') }}" method="POST" class="space-y-3 sm:space-y-4 mb-6">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Field Label</label>
                                <input type="text" name="label" placeholder="Field label (e.g. Pet)" value="{{ old('label') }}"
                                    class="border border-gray-300 rounded-lg p-2 w-full text-sm focus:ring-blue-500 focus:border-blue-500" required>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Field Type</label>
                                <select name="type" id="field-type" class="border border-gray-300 rounded-lg p-2 w-full text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="text">Text Input</option>
                                    <option value="textarea">Text Area</option>
                                    <option value="select">Dropdown (select)</option>
                                    <option value="checkbox">Checkbox (multi)</option>
                                    <option value="radio">Radio (single)</option>
                                    <option value="date">Date Picker</option>
                                    <option value="time">Time Picker</option>
                                    <option value="number">Number Input</option>
                                </select>
                            </div>

                            <label class="flex items-center space-x-2 text-sm">
                                <input type="checkbox" name="required" value="1" class="form-checkbox h-4 w-4 text-blue-600 rounded">
                                <span>Required Field</span>
                            </label>
                        </div>

                        <div id="options-wrapper" class="hidden">
                            <label class="block text-sm text-gray-700">Options (comma separated)</label>
                            <small class="text-gray-500 block text-xs mb-1">Only for select/checkbox/radio — e.g. Dog, Cat, Rabbit</small>
                            <input type="text" name="options" value="{{ old('options') }}" class="border border-gray-300 rounded-lg p-2 w-full text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add Field
                            </button>
                        </div>
                    </form>

                    {{-- Existing Fields Table --}}
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">Existing Form Fields</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Required</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Options</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($fields as $index => $f)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3">
                                            {{ $f->label }}
                                            <span class="sm:hidden block text-xs text-gray-500">{{ ucfirst($f->type) }} {{ $f->required ? '(Required)' : '' }}</span>
                                        </td>
                                        <td class="px-4 py-3 hidden sm:table-cell">
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">{{ ucfirst($f->type) }}</span>
                                        </td>
                                        <td class="px-4 py-3 hidden sm:table-cell">
                                            @if($f->required)
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Yes</span>
                                            @else
                                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">No</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 hidden md:table-cell">
                                            <span class="truncate max-w-[150px] block">{{ $f->options ? implode(', ', $f->options) : '-' }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2 items-center">
                                                <a href="{{ route('clinic.fields.edit', $f->id) }}" class="text-blue-600 hover:text-blue-800 flex items-center text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Edit
                                                </a>

                                                <form action="{{ route('clinic.fields.destroy', $f->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 flex items-center text-sm" onclick="return confirm('Delete this field?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v10m4-10v10m5-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-4 text-center">
                                            <div class="flex flex-col items-center justify-center py-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="text-gray-500 text-sm">No custom fields have been added yet</p>
                                                <p class="text-gray-400 text-xs mt-1">Use the form above to add your first field</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        // small JS to toggle options input
        const typeSelect = document.getElementById('field-type');
        const optionsWrapper = document.getElementById('options-wrapper');

        function toggleOptions() {
            const t = typeSelect.value;
            if (t === 'select' || t === 'checkbox' || t === 'radio') {
                optionsWrapper.classList.remove('hidden');
            } else {
                optionsWrapper.classList.add('hidden');
            }
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', toggleOptions);
            document.addEventListener('DOMContentLoaded', toggleOptions);
        }
    </script>
</x-app-layout>
