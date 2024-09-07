<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, Head } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
});

const form = useForm({
    name: "",
    type: "",
});

const types = [
    'Виды услуг',
    'Виды спорта',
    'Виды товаров',
    'Виды тренировок',
    'Длительность абонементов',
    'Кол-во посещений в неделю',
    'Тренеры',
    'Категории тренеров',
    'Способы оплаты',
];

const submit = () => {
    if (form.type === 'Кол-во посещений в неделю') {
        if (isNaN(form.name) || form.name === '') {
            alert('Пожалуйста, введите число для кол-ва посещений в неделю');
            return;
        }
    } else if(form.type === 'Длительность абонементов') {
        if (isNaN(form.name) || form.name === '') {
            alert('Пожалуйста, введите значение в месяцах для длительности абонемента. Если ваша длительность в неделях - укажите дробное число, используя "точку"');
            return;
        }
    }

    form.post(route('categories.store'), {
        onSuccess: () => form.reset(),
    });
};

const deleteCategory = (category) => {
    if (confirm('Вы уверены, что хотите удалить эту категорию?')) {
        form.delete(route('categories.destroy', category.id));
    }
};
</script>

<template>
    <Head title="Настройка категорий" />

    <AuthenticatedLayout>
        <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Название</label>
                    <input v-model="form.name" type="text" id="name" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div class="mb-4">
                    <label for="type" class="block text-sm font-medium text-gray-700">Тип</label>
                    <select v-model="form.type" id="type" class="mt-1 block w-full" required>
                        <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                    </select>
                    <InputError :message="form.errors.type" class="mt-2" />
                </div>
                <PrimaryButton class="mt-4">Добавить</PrimaryButton>
            </form>

            <div class="mt-6 bg-white shadow-sm rounded-lg divide-y">
                <div v-for="type in types" :key="type" class="p-4">
                    <h2 class="text-lg font-medium text-gray-900">{{ type }}</h2>
                    <ul class="mt-2 text-sm text-gray-500">
                        <li v-for="category in categories.filter(c => c.type === type)" :key="category.id" class="flex justify-between items-center mb-2">
                            {{ category.name }}
                            <button @click="deleteCategory(category)" class="ml-2">Удалить</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
