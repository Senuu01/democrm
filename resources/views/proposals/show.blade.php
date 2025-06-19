<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Proposal Details') }}
            </h2>
            <div class="flex space-x-4">
                <form method="POST" action="{{ route('proposals.sendEmail', $proposal) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700" onclick="return confirm('Send proposal email to {{ $proposal->customer->email }}?')">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Send Email
                    </button>
                </form>
                <a href="{{ route('proposals.edit', $proposal) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    Edit Proposal
                </a>
                <form method="POST" action="{{ route('proposals.destroy', $proposal) }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700" onclick="return confirm('Are you sure you want to delete this proposal?')">
                        Delete Proposal
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Proposal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Title</p>
                            <p class="mt-1 text-lg text-gray-900">{{ $proposal->title }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Customer</p>
                            <p class="mt-1 text-lg text-gray-900">{{ $proposal->customer->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Amount</p>
                            <p class="mt-1 text-lg text-gray-900">${{ number_format($proposal->amount, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($proposal->status === 'accepted') bg-green-100 text-green-800
                                @elseif($proposal->status === 'declined') bg-red-100 text-red-800
                                @elseif($proposal->status === 'sent') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($proposal->status) }}
                            </span>
                            <form method="POST" action="{{ route('proposals.updateStatus', $proposal) }}" class="inline-block ml-4 align-middle">
                                @csrf
                                <select name="status" class="rounded border-gray-300 text-sm px-2 py-1">
                                    <option value="draft" @if($proposal->status=='draft') selected @endif>Draft</option>
                                    <option value="sent" @if($proposal->status=='sent') selected @endif>Sent</option>
                                    <option value="accepted" @if($proposal->status=='accepted') selected @endif>Accepted</option>
                                    <option value="declined" @if($proposal->status=='declined') selected @endif>Declined</option>
                                </select>
                                <button type="submit" class="ml-2 px-3 py-1 bg-indigo-600 text-white rounded text-xs font-semibold hover:bg-indigo-700">Update</button>
                            </form>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Valid Until</p>
                            <p class="mt-1 text-lg text-gray-900">{{ $proposal->valid_until ? \Carbon\Carbon::parse($proposal->valid_until)->format('M d, Y') : '-' }}</p>
                        </div>
                    </div>
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-500">Description</p>
                        <p class="mt-1 text-gray-900">{{ $proposal->description }}</p>
                    </div>
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-500">Terms & Conditions</p>
                        <p class="mt-1 text-gray-900">{{ $proposal->terms_conditions ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 