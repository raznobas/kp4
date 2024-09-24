<script setup>

import dayjs from "dayjs";
import Pagination from "@/Components/Pagination.vue";
import ClientModal from "@/Components/ClientModal.vue";
import {ref} from "vue";

const props = defineProps({
    noShowLeads: {
        type: Object,
    },
});

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
    <h3 class="mt-4 mb-3 text-lg font-medium text-gray-900">Список лидов не пришедших на пробную тренировку</h3>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
        <tr>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Имя</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата пробной</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент</th>
        </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="lead in noShowLeads.data" :key="lead.id">
            <td class="px-3 py-2 whitespace-nowrap">{{ lead.client.id }}</td>
            <td class="px-3 py-2 whitespace-nowrap">
                {{ lead.client.name }} {{ lead.client.surname }}
            </td>
            <td class="px-3 py-2 whitespace-normal">
                {{ lead.training_date ? dayjs(lead.training_date).format('DD.MM.YYYY') : '' }}
            </td>
            <td class="px-3 py-2 whitespace-nowrap">
                <button @click="openModal(lead.client_id)" class="text-indigo-600 hover:text-indigo-900">Карточка</button>
            </td>
        </tr>
        </tbody>
    </table>
    <Pagination :items="noShowLeads" page-param="page_no_show_leads"/>
    <ClientModal :show="showModal" :client="selectedClient"
                 @close="closeModal" @client-updated="handleClientUpdated" />
</template>

<style scoped>
</style>
