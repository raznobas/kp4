<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import {Head, useForm, usePage} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {computed, ref, watch} from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import VueMultiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import Modal from "@/Components/Modal.vue";
import ClientModal from "@/Components/ClientModal.vue";
import ClientLeadForm from "@/Components/ClientLeadForm.vue";
import { useToast } from "@/useToast";
import Pagination from "@/Components/Pagination.vue";
import dayjs from "dayjs";
const { showToast } = useToast();

const props = defineProps(['categories', 'categoryCosts', 'sales']);

const form = useForm({
    sale_date: new Date().toISOString().split('T')[0],
    client_object: null,
    client_id: null,
    director_id: usePage().props.auth.director_id,
    service_or_product: null,
    sport_type: null,
    service_type: null,
    subscription_duration: null,
    visits_per_week: null,
    training_count: null,
    trainer_category: null,
    trainer: null,
    product_type: null,
    subscription_start_date: null,
    subscription_end_date: null,
    cost: null,
    paid_amount: null,
    pay_method: null,
});

const updateFormWithNames = () => {
    const findCategoryNameById = (id, type) => {
        const category = props.categories.find(c => c.id === id && c.type === type);
        return category ? category.name : null;
    };

    form.sport_type = findCategoryNameById(form.sport_type, 'sport_type');
    form.product_type = findCategoryNameById(form.product_type, 'product_type');
    form.subscription_duration = findCategoryNameById(form.subscription_duration, 'subscription_duration');
    form.visits_per_week = findCategoryNameById(form.visits_per_week, 'visits_per_week');
    form.training_count = findCategoryNameById(form.training_count, 'training_count');
    form.trainer_category = findCategoryNameById(form.trainer_category, 'trainer_category');
    form.trainer = findCategoryNameById(form.trainer, 'trainer');
    form.pay_method = findCategoryNameById(form.pay_method, 'pay_method');
};

const updateCost = () => {
    const categoryFields = {
        sport_type: form.sport_type,
        service_type: form.service_type,
        product_type: form.product_type,
        subscription_duration: form.subscription_duration,
        visits_per_week: form.visits_per_week,
        training_count: form.training_count,
        trainer_category: form.trainer_category,
    };
    const categoryIds = Object.keys(categoryFields).map(field => {
        const category = props.categories.find(c => c.id === categoryFields[field]);
        return category ? {field, id: category.id} : null;
    }).filter(item => item !== null);

    let totalCost = 0;

    categoryIds.forEach(item => {
        const categoryCosts = props.categoryCosts.filter(cc => cc.main_category_id == item.id);
        if (categoryCosts.length > 0) {
            // Проверяем, что хотя бы одна из дополнительных категорий соответствует основной категории
            const matchingCost = categoryCosts.find(cc => {
                return cc.additional_costs.every(ac => categoryIds.some(ci => ci.id === ac.additional_category_id));
            });

            if (matchingCost) {
                // Если найдена соответствующая комбинация, добавляем стоимость основной категории
                totalCost += parseFloat(matchingCost.cost);
            }
        }
    });

    form.cost = totalCost;
};
watch(() => [
    form.sport_type,
    form.service_type,
    form.product_type,
    form.subscription_duration,
    form.visits_per_week,
    form.training_count,
    form.trainer_category,
], updateCost);


const submit = () => {
    updateFormWithNames();
    if (form.client_object?.id) {
        form.client_id = form.client_object.id;
        form.post(route('sales.store'), {
            onSuccess: () => {
                form.reset();
                allSumPaid.value = false;
                useTodayDate.value = false;
                showToast("Продажа успешно добавлена!", "success");
            },
            onError: (errors) => {
                Object.values(errors).forEach(error => {
                    showToast(error, "error");
                });
            },
        });
    } else {
        showToast("Выберите клиента для добавления продажи", "info");
    }
};

const useTodayDate = ref(false);
const setTodayDate = () => {
    if (useTodayDate.value) {
        form.subscription_start_date = new Date().toISOString().split('T')[0];
    } else {
        form.subscription_start_date = null;
    }
};
const categoryMap = props.categories.reduce((map, category) => {
    map[category.id] = category.name;
    return map;
}, {});
const calculateEndDate = () => {
    if (form.subscription_start_date && form.subscription_duration) {
        const startDate = new Date(form.subscription_start_date);
        let endDate;
        const durationName = categoryMap[form.subscription_duration];

        // Обработка основных вариантов длительности
        switch (durationName) {
            case '0.03': // значение для разовой
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate());
                break;
            case '0.5': // две недели
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 14);
                break;
            case '1':
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 30);
                break;
            case '3':
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 92);
                break;
            case '6':
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 182);
                break;
            case '12':
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 365);
                break;
            default:
                endDate = null;
                break;
        }

        if (endDate) {
            form.subscription_end_date = endDate.toISOString().split('T')[0];
        }
    } else if (form.subscription_start_date && form.training_count && (categoryMap[form.training_count] === 'Блок 8 тренировок' || categoryMap[form.training_count] === 'Блок 20 тренировок')) {
        // Обработка случая только с "Кол-во тренировок"
        const startDate = new Date(form.subscription_start_date);
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 182);
        form.subscription_end_date = endDate.toISOString().split('T')[0];
    } else {
        form.subscription_end_date = null;
    }
};
watch(() => [
    form.subscription_start_date,
    form.subscription_duration,
    form.training_count,
], calculateEndDate);


// поиск покупателя/лида
const searchResults = ref([]);
const isLoading = ref(false);
const searchClients = async (query) => {
    if (query.length > 1) {
        isLoading.value = true;
        try {
            const url = route('clients.search', {query});
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
    if (option.is_lead) parts.push('(Л)');
    else parts.push('(К)');
    if (option.surname) parts.push(option.surname);
    if (option.name) parts.push(option.name);
    if (option.patronymic) parts.push(option.patronymic);
    return parts.join(' ');
};

// модальное окно
const showModal = ref(false);
const showLeadModal = ref(false);
const selectedClientCard = ref(null);

// Обновляем данные о клиенте после того как с дочернего компонента пришел emit после обновления данных
const handleClientUpdated = (updatedClient) => {
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
const createClient = (formData) => {
    formData.is_lead = false;
    formData.post(route('clients.store'), {
        onSuccess: () => {
            formData.reset();
            showToast("Клиент успешно добавлен!", "success");
            closeModal();
        },
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                showToast(error, "error");
            });
        },
    });
};
const closeModal = () => {
    showModal.value = false;
    showLeadModal.value = false;
    selectedClientCard.value = null;
};

// галочка, которая устанавливает ту же сумму из поля cost в поле paid_amount
const allSumPaid = ref(false);
watch(allSumPaid, (newValue) => {
    if (newValue) {
        form.paid_amount = form.cost;
    } else {
        form.paid_amount = 0;
    }
});

// Условия скрытия полей в зависимости от типов
const isSubscriptionActive = computed(() => form.service_type === 'group' || form.service_type === 'minigroup');
const isTrainingCountActive = computed(() => form.service_type === 'individual' || form.service_type === 'split');
const isServiceActive = computed(() => form.service_or_product === 'service');
const isProductActive = computed(() => form.service_or_product === 'product');
</script>

<template>
    <Head title="Продажи"/>

    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8">
            <h3 class="mb-4">Для работы с продажами директору нужно настроить категории во вкладке "Настройка
                категорий".</h3>
            <PrimaryButton type="button" @click="showLeadModal = true;">+ Новый клиент</PrimaryButton>
            <Modal :show="showLeadModal" @close="closeModal">
                <ClientLeadForm :is-lead="false" @submit="createClient"/>
            </Modal>
            <form @submit.prevent="submit">
                <div class="flex flex-row flex-wrap gap-2 auto-cols-max items-end mt-2">
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
                                <button type="button" @click="openModal(form.client_object)"
                                        class="text-indigo-600 hover:text-indigo-900">(карточка)</button>
                            </span>
                        </label>
                        <vue-multiselect
                            id="fio"
                            v-model="form.client_object"
                            :options="searchResults"
                            :searchable="true"
                            :max-height="400"
                            :options-limit="200"
                            :placeholder="'Поиск'"
                            :show-labels="false"
                            :custom-label="fullName"
                            :internal-search="false"
                            :loading="isLoading"
                            track-by="id"
                            @search-change="searchClients"
                        >
                            <template v-slot:option="props">
                                {{ fullName(props.option) }}
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
                    <div class="flex flex-col">
                        <label for="service_or_product" class="text-sm font-medium text-gray-700">Услуга/Товар</label>
                        <select id="service_or_product" required v-model="form.service_or_product"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option value="service">Услуга</option>
                            <option value="product">Товар</option>
                        </select>
                        <InputError :message="form.errors.service_or_product" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': !isServiceActive }">
                        <label for="sport_type" class="text-sm font-medium text-gray-700">Вид спорта</label>
                        <select id="sport_type" v-model="form.sport_type"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option v-if="categories.filter(c => c.type === 'sport_type').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'sport_type')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.sport_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': !isServiceActive }">
                        <label for="service_type" class="text-sm font-medium text-gray-700">Вид услуги</label>
                        <select id="service_type" v-model="form.service_type"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                                @change="updateServiceType">
                            <option value="trial">Пробная</option>
                            <option value="group">Групповая</option>
                            <option value="minigroup">Минигруппа</option>
                            <option value="individual">Индивидуальная</option>
                            <option value="split">Сплит</option>
                        </select>
                        <InputError :message="form.errors.service_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': !isProductActive }">
                        <label for="product_types" class="text-sm font-medium text-gray-700">Вид товара</label>
                        <select id="product_types" v-model="form.product_type"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-if="categories.filter(c => c.type === 'product_type').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'product_type')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.product_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32"
                         :class="{ 'disabled-field': !isServiceActive || !isSubscriptionActive }">
                        <label for="subscription_duration"
                               class="text-sm font-medium text-gray-700">Длительность абонемента</label>
                        <select id="subscription_duration" v-model="form.subscription_duration"
                                @change="calculateEndDate"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                                :disabled="!isSubscriptionActive">
                            <option v-if="categories.filter(c => c.type === 'subscription_duration').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'subscription_duration')"
                                    :value="category.id" :key="category.id">
                                {{ category.name === '0.03' ? 'Разовая' : category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.subscription_duration" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32"
                         :class="{ 'disabled-field': !isServiceActive }">
                        <label for="visits_per_week" class="text-sm font-medium text-gray-700">Посещений в
                            неделю</label>
                        <select id="visits_per_week" v-model="form.visits_per_week"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-if="categories.filter(c => c.type === 'visits_per_week').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'visits_per_week')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.visits_per_week" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': !isServiceActive || !isTrainingCountActive }">
                        <label for="training_count" class="text-sm font-medium text-gray-700">Кол-во тренировок</label>
                        <select id="training_count" v-model="form.training_count"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                                :disabled="!isTrainingCountActive">
                            <option v-if="categories.filter(c => c.type === 'training_count').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'training_count')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.training_count" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': !isServiceActive }">
                        <label for="trainer_category" class="text-sm font-medium text-gray-700">Категория
                            тренера</label>
                        <select id="trainer_category" v-model="form.trainer_category"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-if="categories.filter(c => c.type === 'trainer_category').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'trainer_category')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.trainer_category" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': !isServiceActive }">
                        <label for="trainer" class="text-sm font-medium text-gray-700">Тренер</label>
                        <select id="trainer" v-model="form.trainer"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md">
                            <option v-if="categories.filter(c => c.type === 'trainer').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'trainer')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.trainer_category" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32"
                         :class="{ 'disabled-field': !isServiceActive }">
                        <label for="subscription_start_date" class="text-sm font-medium text-gray-700">Начало
                            абонемента</label>
                        <div class="-mt-1">
                            <input id="todayCheckbox" class="w-3 h-3 p-0" type="checkbox" v-model="useTodayDate"
                                   @change="setTodayDate"/>
                            <label for="todayCheckbox" class="ml-1 text-xs text-gray-700 cursor-pointer">Сегодня</label>
                        </div>
                        <input id="subscription_start_date" type="date" v-model="form.subscription_start_date"
                               @change="calculateEndDate"
                               class="p-1 border border-gray-300 rounded-md"
                               :disabled="useTodayDate"
                        />
                        <InputError :message="form.errors.subscription_start_date" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32"
                         :class="{ 'disabled-field': !isServiceActive }">
                        <label for="subscription_end_date" class="text-sm font-medium text-gray-700">Окончание
                            абонемента</label>
                        <input id="subscription_end_date" type="date" v-model="form.subscription_end_date"
                               class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.subscription_end_date" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-24">
                        <label for="cost" class="text-sm font-medium text-gray-700">Стоимость</label>
                        <input id="cost" type="number" min="0" step="1" v-model="form.cost"
                               class="mt-1 p-1 border border-gray-300 rounded-md" required/>
                        <InputError :message="form.errors.cost" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-24">
                        <label for="paid_amount" class="text-sm font-medium text-gray-700">Сумма оплач.</label>
                        <div class="-mt-1">
                            <input id="allSumPaid" class="w-3 h-3 p-0" type="checkbox" v-model="allSumPaid"/>
                            <label for="allSumPaid" class="ml-1 text-xs text-gray-700 cursor-pointer">Вся сумма</label>
                        </div>
                        <input id="paid_amount" type="number" min="0" step="1" v-model="form.paid_amount"
                               class="p-1 border border-gray-300 rounded-md" required/>
                        <InputError :message="form.errors.paid_amount" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32">
                        <label for="pay_method" class="text-sm font-medium text-gray-700">Способ оплаты</label>
                        <select id="pay_method" v-model="form.pay_method"
                                class="mt-1 p-1 pe-8 border border-gray-300 rounded-md"
                        >
                            <option v-if="categories.filter(c => c.type === 'pay_method').length === 0" value="" disabled>
                                Ничего нет
                            </option>
                            <option v-for="category in categories.filter(c => c.type === 'pay_method')"
                                    :value="category.id" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.pay_method" class="mt-2 text-sm text-red-600"/>
                    </div>
                </div>
                <div class="mt-4">
                    <PrimaryButton :disabled="form.processing">Добавить продажу</PrimaryButton>
                    <SecondaryButton class="ml-2" type="button"
                                     @click="() => { form.reset(); selectedClientCard = null }">Очистить
                    </SecondaryButton>
                </div>
            </form>
            <ClientModal :show="showModal" :client="selectedClientCard" @close="closeModal"
                         @client-updated="handleClientUpdated"/>
            <div>
                <h3 class="mt-8 mb-4 text-lg font-medium text-gray-900">Список всех продаж вашей организации</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Имя</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Вид спорта/товара</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Вид услуги</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Абонемент/Посещ. в нед.</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кол-во трен-вок</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тренер</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Начало абонем.</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Конец абонем.</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Цена/Всего оплач.</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Способ опл.</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="sale in sales.data" :key="sale.id">
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ sale.sale_date ? dayjs(sale.sale_date).format('DD.MM.YY') : '' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ sale.client?.surname }} {{ sale.client?.name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ sale.sport_type ?? sale.product_type }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span v-if="sale.service_type === 'trial'">Пробная</span>
                                <span v-else-if="sale.service_type === 'group'">Групповая</span>
                                <span v-else-if="sale.service_type === 'minigroup'">Минигруппа</span>
                                <span v-else-if="sale.service_type === 'individual'">Индивидуальная</span>
                                <span v-else-if="sale.service_type === 'split'">Сплит</span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ sale.subscription_duration }}
                                <span v-if="sale.subscription_duration && sale.visits_per_week">/</span>
                                {{ sale.visits_per_week}}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ sale.training_count }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ sale.trainer }}
                                <span v-if="sale.trainer && sale.trainer_category">/</span>
                                {{ sale.trainer_category}}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ sale.subscription_start_date ? dayjs(sale.subscription_start_date).format('DD.MM.YY') : '' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ sale.subscription_end_date ? dayjs(sale.subscription_end_date).format('DD.MM.YY') : '' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ sale.cost ? Number(sale.cost).toFixed(0) : '0' }}
                                <span v-if="sale.cost && sale.paid_amount">/</span>
                                {{ sale.paid_amount ? Number(sale.paid_amount).toFixed(0) : '0' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ sale.pay_method }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <button @click="openModal(sale.client_id)" class="text-indigo-600 hover:text-indigo-900">Карточка</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :items="sales" />
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
