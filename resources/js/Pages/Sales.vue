<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import {Head, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref } from "vue";

const form = useForm({
    sale_date: new Date().toISOString().split('T')[0],
    name: null,
    surname: null,
    patronymic: null,
    service_or_product: null,
    sport_type: null,
    service_type: null,
    subscription_duration: null,
    visits_per_week: null,
    training_count: null,
    trainer_category: null,
    product_types: null,
    subscription_start_date: null,
    subscription_end_date: null,
    subscription_cost: null,
    paid_amount: null,
    pay_method: null,
    phone: null,
});

defineProps(['categories']);

const submit = () => {
    form.post(route('sales.store'), {
        onSuccess: () => form.reset(),
    });
};

const useTodayDate = ref(false);
const setTodayDate = () => {
    if (useTodayDate.value) {
        form.subscription_start_date = new Date().toISOString().split('T')[0];
    } else {
        form.subscription_start_date = null;
    }
};

const calculateEndDate = () => {
    if (form.subscription_start_date && form.subscription_duration) {
        const startDate = new Date(form.subscription_start_date);
        let endDate;

        switch (form.subscription_duration) {
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
    } else {
        form.subscription_end_date = null;
    }
};

</script>

<template>
    <Head title="Продажи"/>

    <AuthenticatedLayout>
        <div class="mx-auto p-4 sm:p-6 lg:p-8">
            <h3 class="mb-4">Для работы с продажами директору нужно настроить категории во вкладке "Настройка категорий".</h3>
            <form @submit.prevent="submit">
                <div class="grid grid-cols-7 gap-2 items-end">
                    <div class="col-span-1 w-32">
                        <label for="sale_date" class="text-sm font-medium text-gray-700">Дата продажи</label>
                        <input type="date" v-model="form.sale_date"
                               class="mt-1 p-1 border border-gray-300 rounded-md"
                        />
                        <InputError :message="form.errors.sale_date" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="surname" class="text-sm font-medium text-gray-700">Фамилия</label>
                        <input type="text" v-model="form.surname" class="mt-1 p-1 border border-gray-300 rounded-md"
                        />
                        <InputError :message="form.errors.surname" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="name" class="text-sm font-medium text-gray-700">Имя</label>
                        <input type="text" required v-model="form.name" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.name" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="patronymic" class="text-sm font-medium text-gray-700">Отчество (необяз.)</label>
                        <input type="text" v-model="form.patronymic"
                               class="mt-1 p-1 border border-gray-300 rounded-md"
                        />
                        <InputError :message="form.errors.patronymic" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="service_or_product" class="text-sm font-medium text-gray-700">Услуга/Товар</label>
                        <select required v-model="form.service_or_product" class="mt-1 p-1 border border-gray-300 rounded-md"
                        >
                            <option value="service">Услуга</option>
                            <option value="product">Товар</option>
                        </select>
                        <InputError :message="form.errors.service_or_product" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': form.service_or_product === 'product' }">
                        <label for="sport_type" class="text-sm font-medium text-gray-700">Вид спорта</label>
                        <select v-model="form.sport_type" class="mt-1 p-1 border border-gray-300 rounded-md"
                        >
                            <option v-for="category in categories.filter(c => c.type === 'Виды спорта')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.sport_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': form.service_or_product === 'product' }">
                        <label for="service_type" class="text-sm font-medium text-gray-700">Вид услуги</label>
                        <select v-model="form.service_type" class="mt-1 p-1 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'Виды услуг')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.service_type" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': form.service_or_product === 'service' }">
                        <label for="product_types" class="text-sm font-medium text-gray-700">Вид товара</label>
                        <select v-model="form.product_types" class="mt-1 p-1 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'Виды товаров')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.product_types" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': form.service_or_product === 'product' }">
                        <label for="subscription_duration"
                               class="text-sm font-medium text-gray-700">Длительность абонемента</label>
                        <select v-model="form.subscription_duration" @change="calculateEndDate"
                                class="mt-1 p-1 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'Длительность абонементов')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.subscription_duration" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': form.service_or_product === 'product' }">
                        <label for="visits_per_week" class="text-sm font-medium text-gray-700">Кол-во посещений в неделю</label>
                        <select v-model="form.visits_per_week" class="mt-1 p-1 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'Кол-во посещений в неделю')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.visits_per_week" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': form.service_or_product === 'product' }">
                        <label for="training_count" class="text-sm font-medium text-gray-700">Вид
                            тренировки</label>
                        <select v-model="form.training_count" class="mt-1 p-1 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'Виды тренировок')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.training_count" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col" :class="{ 'disabled-field': form.service_or_product === 'product' }">
                        <label for="trainer_category" class="text-sm font-medium text-gray-700">Категория тренера</label>
                        <select v-model="form.trainer_category" class="mt-1 p-1 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'Категории тренеров')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.trainer_category" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col">
                        <label for="phone" class="text-sm font-medium text-gray-700">Телефон</label>
                        <input type="text" v-model="form.phone" class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.phone" class="mt-2 text-sm text-red-600"/>
                    </div>
                </div>
                <div class="grid grid-flow-col auto-cols-max gap-2 mt-2 items-end">
                    <div class="flex flex-col" :class="{ 'disabled-field': form.service_or_product === 'product' }">
                        <label for="subscription_start_date" class="text-sm font-medium text-gray-700">Дата начала абонемента</label>
                        <div class="-mt-1">
                            <input id="todayCheckbox" class="w-3 h-3 p-0" type="checkbox" v-model="useTodayDate" @change="setTodayDate" />
                            <label for="todayCheckbox" class="ml-1 text-xs text-gray-700 cursor-pointer">Сегодня</label>
                        </div>
                        <input type="date" v-model="form.subscription_start_date"
                               @change="calculateEndDate"
                               class=" p-1 border border-gray-300 rounded-md"
                               :disabled="useTodayDate"
                            />
                        <InputError :message="form.errors.subscription_start_date" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32" :class="{ 'disabled-field': form.service_or_product === 'product' }">
                        <label for="subscription_end_date" class="text-sm font-medium text-gray-700">Дата окончания
                            абонемента</label>
                        <input type="date" v-model="form.subscription_end_date"
                               class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.subscription_end_date" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32" :class="{ 'disabled-field': form.service_or_product === 'product' }">
                        <label for="subscription_cost" class="text-sm font-medium text-gray-700">Стоимость
                            абонемента</label>
                        <select v-model="form.subscription_cost" class="mt-1 p-1 border border-gray-300 rounded-md">
                            <option v-for="category in categories.filter(c => c.type === 'стоимость абонемента')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.subscription_cost" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32">
                        <label for="paid_amount" class="text-sm font-medium text-gray-700">Оплаченная сумма</label>
                        <input type="number" step="0.01" v-model="form.paid_amount"
                               class="mt-1 p-1 border border-gray-300 rounded-md"/>
                        <InputError :message="form.errors.paid_amount" class="mt-2 text-sm text-red-600"/>
                    </div>
                    <div class="flex flex-col w-32">
                        <label for="paid_amount" class="text-sm font-medium text-gray-700">Способ оплаты</label>
                        <select v-model="form.pay_method" class="mt-1 p-1 border border-gray-300 rounded-md"
                        >
                            <option v-for="category in categories.filter(c => c.type === 'Способы оплаты')"
                                    :value="category.name" :key="category.id">{{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.paid_amount" class="mt-2 text-sm text-red-600"/>
                    </div>
                </div>
                <div class="mt-4">
                    <PrimaryButton :disabled="form.processing">Добавить продажу</PrimaryButton>
                </div>
            </form>
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
