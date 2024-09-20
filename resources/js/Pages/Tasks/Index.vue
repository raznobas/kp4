<script setup>

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs";
import {Head, useForm, usePage} from "@inertiajs/vue3";
import ClientModal from "@/Components/ClientModal.vue";
import {ref} from "vue";
import Pagination from "@/Components/Pagination.vue";
import NoShowLeads from "@/Pages/Tasks/Partials/NoShowLeads.vue";

const props = defineProps(['tasks', 'noShowLeads']);

const showModal = ref(false);
const selectedClient = ref(null);

const openModal = async (clientId) => {
    try {
        selectedClient.value = (await axios.get(route('clients.show', clientId))).data;
        showModal.value = true;
    } catch (error) {
        console.error('Ошибка при получении данных клиента:', error);
    }
};
const handleClientUpdated = (updatedClient) => {
    // Обновляем данные о клиенте после того как с дочернего компонента пришел emit после обновления данных
    selectedClient.value = updatedClient;
};

const closeModal = () => {
    showModal.value = false;
    selectedClient.value = null;
};
</script>

<template>
    <Head title="Задачи"/>

    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Список всех задач по всем клиентам/лидам</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Описание задачи</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="task in tasks.data" :key="task.id">
                    <td class="px-3 py-2 whitespace-nowrap">{{ task.id }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        {{ task.task_date ? dayjs(task.task_date).format('DD.MM.YYYY') : '' }}
                    </td>
                    <td class="px-3 py-2 whitespace-normal">
                        {{ task.task_description }}
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        <button @click="openModal(task.client.id)" class="text-indigo-600 hover:text-indigo-900">Карточка</button>
                    </td>
                </tr>
                </tbody>
            </table>
            <Pagination :items="tasks" />
            <ClientModal :show="showModal" :client="selectedClient"
                         @close="closeModal" @client-updated="handleClientUpdated" />
            <NoShowLeads :no-show-leads="noShowLeads"/>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
</style>
