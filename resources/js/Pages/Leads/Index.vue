<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import {Head, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {computed, ref, watch} from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import VueMultiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import ClientModal from "@/Components/ClientModal.vue";
import Modal from "@/Components/Modal.vue";
import ClientLeadForm from "@/Components/ClientLeadForm.vue";

const props = defineProps(['categories']);

const form = useForm({
    sale_date: new Date().toISOString().split('T')[0],
    client_object: null,
    service_or_product: null,
    sport_type: null,
    service_type: null,
    trainer: null,
    date_appointment: null,
    time_appointment: null,
    hasAppointment: false,
});

const submit = () => {
    form.post(route('sales.store'), {
        onSuccess: () => form.reset(),
    });
};

// поиск клиента
const searchResults = ref([]);

const searchClients = async (query, isLead = true) => {
    if (query.length > 2) {
        try {
            const url = route('clients.search', { query, is_lead: isLead });
            const response = await axios.get(url);
            searchResults.value = response.data;
        } catch (error) {
            console.error('Ошибка при поиске клиентов:', error);
        }
    } else {
        searchResults.value = [];
    }
};
const fullName = (option) => {
    const parts = [];
    if (option.surname) parts.push(option.surname);
    if (option.name) parts.push(option.name);
    if (option.patronymic) parts.push(option.patronymic);
    return parts.join(' ');
};

// модальное окно
const showModal = ref(false);
const showLeadModal = ref(false);
const selectedClientCard = ref(null);
const handleClientUpdated = (updatedClient) => {
    // Обновляем данные о клиенте после того как с дочернего компонента пришел emit после обновления данных
    selectedClientCard.value = updatedClient;
};
const openModal = async (clientId) => {
    try {
        selectedClientCard.value = (await axios.get(route('clients.show', clientId))).data;
        showModal.value = true;
    } catch (error) {
        console.error('Ошибка при получении данных клиента:', error);
    }
};

const closeModal = () => {
    showModal.value = false;
    showLeadModal.value = false;
    selectedClientCard.value = null;
};
const createLead = (formData) => {
    formData.is_lead = true;
    formData.post(route('clients.store'), {
        onSuccess: () => formData.reset(),
    });
    closeModal();
};

// сортировка для передачи в компонент только категорий источников
const adSourceCategories = computed(() => {
    return props.categories.filter(category => category.type === 'ad_source');
});
</script>

<template>
    <Head title="Лиды"/>

    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8">
            <PrimaryButton type="button" @click="showLeadModal = true;">+ Новый лид</PrimaryButton>
            <Modal :show="showLeadModal" @close="closeModal">
                <ClientLeadForm :is-lead="true" :source-options="adSourceCategories" @submit="createLead" />
            </Modal>
            <form @submit.prevent="submit" class="mt-6">
                <div class="flex flex-row flex-wrap gap-2 items-end mt-2">
                    <div class="flex flex-col col-span-1 w-32">
                        <label for="sale_date" class="text-sm font-medium text-gray-700">Дата продажи</label>
                        <input id="sale_date" type="date" v-model="form.sale_date"
                               class="mt-1 p-1 border border-gray-300 rounded-md"
                        />
                        <InputError :message="form.errors.sale_date" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-56 relative">
                        <label for="fio" class="text-sm font-medium text-gray-700">Имя
                            <span v-if="form.client_object">
                                <button type="button" @click="openModal(form.client_object)" class="text-indigo-600 hover:text-indigo-900">(карточка)</button>
                            </span>
                        </label>
                        <vue-multiselect id="fio"
                            v-model="form.client_object"
                            :options="searchResults"
                            :searchable="true"
                            :max-height="400"
                            :options-limit="200"
                            :placeholder="'Поиск'"
                            :show-labels="false"
                            :custom-label="fullName"
                            :internal-search="false"
                            track-by="id"
                            @search-change="searchClients"
                        >
                            <template v-slot:option="props">
                                {{ props.option.surname }} {{ props.option.name }} {{ props.option.patronymic }}
                            </template>
                        </vue-multiselect>
                    </div>
                    <div class="flex flex-col">
                        <label for="service_or_product" class="text-sm font-medium text-gray-700">Услуга/Товар</label>
                        <select id="service_or_product" required v-model="form.service_or_product" class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option value="service">Услуга</option>
                            <option value="product">Товар</option>
                        </select>
                        <InputError :message="form.errors.service_or_product" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="sport_type" class="text-sm font-medium text-gray-700">Вид спорта</label>
                        <select id="sport_type" v-model="form.sport_type" class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option v-for="category in categories.filter(c => c.type === 'sport_type')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.sport_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="service_type" class="text-sm font-medium text-gray-700">Вид услуги</label>
                        <select id="service_type" v-model="form.service_type" class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option value="group">Групповая</option>
                            <option value="minigroup">Минигруппа</option>
                            <option value="individual">Индивидуальная</option>
                            <option value="split">Сплит</option>
                        </select>
                        <InputError :message="form.errors.service_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="trainer" class="text-sm font-medium text-gray-700">Тренер</label>
                        <select id="trainer" v-model="form.trainer" class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option v-for="category in categories.filter(c => c.type === 'trainer')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.trainer" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-sm font-medium text-gray-700">Запись</label>
                        <input type="checkbox" v-model="form.hasAppointment" class="mt-1 p-3 border border-gray-300 rounded-md"/>
                    </div>
                    <div class="flex flex-col w-32" :class="{ 'disabled-field': !form.hasAppointment }">
                        <label for="date_appointment" class="text-sm font-medium text-gray-700">Дата записи</label>
                        <input id="date_appointment" type="date" v-model="form.date_appointment"
                               class="mt-1 p-1 border border-gray-300 rounded-md"
                               :disabled="!form.hasAppointment"
                        />
                    </div>
                    <div class="flex flex-col w-32" :class="{ 'disabled-field': !form.hasAppointment }">
                        <label for="time_appointment" class="text-sm font-medium text-gray-700">Время записи</label>
                        <input id="time_appointment" type="time" v-model="form.time_appointment"
                               class="mt-1 p-1 border border-gray-300 rounded-md"
                               :disabled="!form.hasAppointment"
                        />
                    </div>
                </div>
                <div class="mt-4">
                    <PrimaryButton :disabled="form.processing">Добавить запись</PrimaryButton>
                    <SecondaryButton class="ml-2" type="button" @click="() => { form.reset(); }">Очистить</SecondaryButton>
                </div>
            </form>
            <ClientModal :show="showModal" :client="selectedClientCard" @close="closeModal" @client-updated="handleClientUpdated" />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.disabled-field {
    opacity: 0.5;
    pointer-events: none;
    background-color: #f0f0f0;
    border-color: #ccc;
}
</style>
