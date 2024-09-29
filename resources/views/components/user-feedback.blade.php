@props(['action'])

<div x-data="{ showFeedback: false, rating: 0 }" class="mt-8">
    <button @click="showFeedback = true" class="text-sm text-blue-600 hover:text-blue-800">
        Provide feedback
    </button>

    <div x-show="showFeedback" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">How was your experience?</h3>
            <div class="flex justify-center space-x-2 mb-4">
                <template x-for="i in 5">
                    <button @click="rating = i" class="text-2xl" :class="{ 'text-yellow-400': rating >= i, 'text-gray-300': rating < i }">
                        ★
                    </button>
                </template>
            </div>
            <form action="{{ $action }}" method="POST">
                @csrf
                <input type="hidden" name="rating" x-model="rating">
                <textarea name="comment" class="w-full h-24 p-2 border rounded-md mb-4" placeholder="Any additional comments?"></textarea>
                <div class="flex justify-end space-x-2">
                    <button @click="showFeedback = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>