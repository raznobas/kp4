<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import {Head, useForm, usePage} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {computed, ref, watch} from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import VueMultiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import ClientModal from "@/Components/ClientModal.vue";
import Modal from "@/Components/Modal.vue";
import ClientLeadForm from "@/Components/ClientLeadForm.vue";
import dayjs from "dayjs";
import customParseFormat from 'dayjs/plugin/customParseFormat';

dayjs.extend(customParseFormat);
import Pagination from "@/Components/Pagination.vue";
import {useToast} from "@/useToast";

const {showToast} = useToast();

const props = defineProps(['categories', 'leads', 'leadAppointments']);

const form = useForm({
    id: null, // символизирует о том, что активно редактирование
    client_object: null,
    client_id: null,
    director_id: usePage().props.auth.director_id,
    sport_type: null,
    service_type: null,
    trainer: null,
    training_date: null,
    training_time: null,
});

const submit = () => {
    if (form.client_object?.id) {
        form.client_id = form.client_object.id;
        form.post(route('leads.store'), {
            onSuccess: () => {
                form.reset();
                showToast("Запись успешно добавлена!", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    } else {
        showToast("Выберите лида для добавления записи", "info");
    }
};

// поиск клиента
const searchResults = ref([]);
const isLoading = ref(false);
const searchClients = async (query, isLead = true) => {
    if (query.length > 1) {
        isLoading.value = true;
        try {
            const url = route('clients.search', {query, is_lead: isLead});
            const response = await axios.get(url);
            searchResults.value = response.data;
        } catch (error) {
            showToast("Ошибка поиска: " + error.message, "error");
        } finally {
            isLoading.value = false;
        }
    } else {
        searchResults.value = [];
        isLoading.value = false;
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
    form.client_object = updatedClient;
};
const openModal = async (clientId) => {
    try {
        selectedClientCard.value = (await axios.get(route('clients.show', clientId))).data;
        showModal.value = true;
    } catch (error) {
        showToast("Ошибка получения данных: " + error.message, "error");
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
        onSuccess: () => {
            form.reset();
            showToast("Лид успешно добавлен!", "success");
            closeModal();
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};

// ограничение ставить время раньше текущего если выбрана сегодняшняя дата
const currentDate = new Date().toISOString().split('T')[0];
const currentTime = new Date().toTimeString().split(' ')[0].split(':').slice(0, 2).join(':');

watch(() => form.training_date, (newDate) => {
    if (newDate === currentDate) {
        if (form.training_time < currentTime) {
            form.training_time = currentTime;
        }
    }
});

watch(() => form.training_time, (newTime) => {
    if (form.training_date === currentDate && newTime < currentTime) {
        form.training_time = currentTime;
    }
});

const editAppointment = (appointment) => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
    form.id = appointment.id; // form.id символизирует о том, что активно редактирование
    form.sale_date = appointment.sale_date;
    form.client_object = {
        id: appointment.client_id,
        surname: appointment.client.surname,
        name: appointment.client.name,
        patronymic: appointment.client.patronymic,
        phone: appointment.client.phone,
        ad_source: appointment.client.ad_source,
    };
    form.client_id = appointment.client_id;
    form.sport_type = appointment.sport_type;
    form.service_type = appointment.service_type;
    form.trainer = appointment.trainer;
    form.training_date = appointment.training_date;
    form.training_time = appointment.training_time;
    form.hasAppointment = true;
};
const submitEdit = () => {
    form.client_id = form.client_object.id;
    form.put(route('leads.update', {id: form.id}), {
        onSuccess: () => {
            form.reset();
            showToast("Запись успешно обновлена!", "success");
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};
const deleteAppointment = async (appointmentId) => {
    if (confirm('Вы уверены, что хотите удалить эту запись?')) {
        form.delete(route('leads.destroy', appointmentId), {
            onSuccess: () => {
                showToast("Запись успешно удалена!", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    }
};
</script>

<template>
    <Head title="Лиды"/>

    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8">
            <PrimaryButton type="button" @click="showLeadModal = true;">+ Новый лид</PrimaryButton>
            <Modal :show="showLeadModal" @close="closeModal">
                <ClientLeadForm :is-lead="true" @submit="createLead"/>
            </Modal>
            <form @submit.prevent="submit" class="mt-6">
                <h3 v-if="form.id" class="mt-8 mb-4 text-lg font-medium text-gray-900">Редактирование записи лида</h3>
                <div class="flex flex-row flex-wrap gap-2 items-end mt-2">
                    <div class="flex flex-col w-56 relative">
                        <label for="fio" class="text-sm font-medium text-gray-700">Имя
                            <span v-if="form.client_object">
                                <button type="button" @click="openModal(form.client_object)"
                                        class="text-indigo-600 hover:text-indigo-900">(карточка)</button>
                            </span>
                        </label>
                        <vue-multiselect id="fio"
                                         v-model="form.client_object"
                                         :options="searchResults"
                                         :searchable="true"
                                         :max-height="400"
                                         :options-limit="200"
                                         :placeholder="'Поиск по имени'"
                                         :show-labels="false"
                                         :custom-label="fullName"
                                         :internal-search="false"
                                         :loading="isLoading"
                                         track-by="id"
                                         @search-change="searchClients"
                        >
                            <template v-slot:option="props">
                                {{ props.option.surname }} {{ props.option.name }} {{ props.option.patronymic }}
                            </template>
                            <template v-slot:noOptions>
                                <span class="text-gray-500">Введите имя</span>
                            </template>
                            <template v-slot:noResult>
                                <span class="text-gray-500">Ничего не найдено</span>
                            </template>
                        </vue-multiselect>
                    </div>
                    <div v-if="form.client_object" class="flex flex-col w-32 cursor-not-allowed">
                        <label for="phone" class="text-sm font-medium text-gray-700">Телефон</label>
                        <input disabled :placeholder="form.client_object?.phone ?? 'Отсутствует'"
                               type="text" class="p-1 border border-gray-300 rounded-md"/>
                    </div>
                    <div v-if="form.client_object" class="flex flex-col w-32 cursor-not-allowed">
                        <label for="phone" class="text-sm font-medium text-gray-700">Источник</label>
                        <input disabled :placeholder="form.client_object?.ad_source ?? 'Отсутствует'"
                               type="text" class="p-1 border border-gray-300 rounded-md"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="sport_type" class="text-sm font-medium text-gray-700">Вид спорта</label>
                        <select id="sport_type" v-model="form.sport_type"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option v-if="categories.filter(c => c.type === 'sport_type').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'sport_type')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.sport_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="service_type" class="text-sm font-medium text-gray-700">Вид услуги</label>
                        <select id="service_type" v-model="form.service_type"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option value="group">Групповая</option>
                            <option value="minigroup">Минигруппа</option>
                            <option value="individual">Индивидуальная</option>
                            <option value="split">Сплит</option>
                        </select>
                        <InputError :message="form.errors.service_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="trainer" class="text-sm font-medium text-gray-700">Тренер</label>
                        <select id="trainer" v-model="form.trainer"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option v-if="categories.filter(c => c.type === 'trainer').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'trainer')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.trainer" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32">
                        <label for="training_date" class="text-sm font-medium text-gray-700">Дата записи</label>
                        <input id="training_date" type="date" v-model="form.training_date"
                               class="mt-1 p-1 border border-gray-300 rounded-md"
                                required
                        />
                        <InputError class="mt-2" :message="form.errors.training_date"/>
                    </div>
                    <div class="flex flex-col w-32" >
                        <label for="training_time" class="text-sm font-medium text-gray-700">Время записи</label>
                        <input id="training_time" type="time" v-model="form.training_time"
                               class="mt-1 p-1 border border-gray-300 rounded-md"
                        />
                    </div>
                </div>
                <div class="mt-4">
                    <PrimaryButton v-if="!form.id" :disabled="form.processing">Добавить запись</PrimaryButton>
                    <PrimaryButton v-else type="button" :disabled="form.processing" @click="submitEdit()">
                       Редактировать запись
                    </PrimaryButton>
                    <SecondaryButton class="ml-2" type="button" @click="() => { form.reset(); }">
                        {{ form.id ? 'Отмена' : 'Очистить' }}
                    </SecondaryButton>
                </div>
            </form>
            <ClientModal :show="showModal" :client="selectedClientCard"
                         @close="closeModal" @client-updated="handleClientUpdated"/>
            <div>
                <h3 class="mt-8 mb-4 text-lg font-medium text-gray-900">Записи на пробную тренировку</h3>
                <div v-if="leadAppointments.data && leadAppointments.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Имя
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Вид
                                    спорта
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Вид
                                    услуги
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Тренер
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Дата/время тренировки
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Действия
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="appointment in leadAppointments.data" :key="appointment.id">
                                <td class="px-3 py-2 whitespace-nowrap">{{ appointment.id }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ appointment.client?.surname }} {{ appointment.client?.name }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ appointment.sport_type }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span v-if="appointment.service_type === 'group'">Групповая</span>
                                    <span v-else-if="appointment.service_type === 'minigroup'">Минигруппа</span>
                                    <span v-else-if="appointment.service_type === 'individual'">Индивидуальная</span>
                                    <span v-else-if="appointment.service_type === 'split'">Сплит</span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ appointment.trainer }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ appointment.training_date ? dayjs(appointment.training_date).format('DD.MM.YYYY') : '' }}
                                    <span v-if="appointment.training_time">/
                              {{ dayjs(appointment.training_time, "HH:mm:ss").format('HH:mm') }}
                            </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <button @click="openModal(appointment.client_id)"
                                            class="text-indigo-600 hover:text-indigo-900">Карточка
                                    </button>
                                    <span class="ms-4">
                                    <button title="Редактировать" type="button" @click="editAppointment(appointment)" class="px-1">
                                        <i class="fa fa-pencil text-blue-600" aria-hidden="true"></i>
                                    </button>
                                    <button @click="deleteAppointment(appointment.id)" class="px-1 ms-1"
                                            title="Удалить запись">
                                        <i class="fa fa-trash text-red-600" aria-hidden="true"></i>
                                    </button>
                            </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :items="leadAppointments" page-param="page_appointments"/>
                </div>
                <div v-else class="text-gray-500">
                    Ничего не найдено
                </div>
            </div>
            <div>
                <h3 class="mt-8 mb-4 text-lg font-medium text-gray-900">Список лидов вашей организации</h3>
                <div v-if="leads.data && leads.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Фамилия
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Имя
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Отчество
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Дата
                                    рождения
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Телефон
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Почта
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Действия
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="lead in leads.data" :key="lead.id">
                                <td class="px-3 py-2 whitespace-nowrap">{{ lead.id }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ lead.surname }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ lead.name }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ lead.patronymic }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    {{ lead.birthdate ? dayjs(lead.birthdate).format('DD.MM.YYYY') : '' }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ lead.phone }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ lead.email }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <button @click="openModal(lead.id)" class="text-indigo-600 hover:text-indigo-900">
                                        Карточка
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :items="leads" page-param="page"/>
                </div>
                <div v-else class="text-gray-500">
                    Ничего не найдено
                </div>
            </div>
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
