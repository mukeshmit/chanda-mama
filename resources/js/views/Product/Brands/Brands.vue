<template>
    <div>
        <div class="page-heading">
            <div class="page-heading d-flex justify-content-between align-items-center mb-4">
                <h3 class="modern-page-title mb-0">{{ __('brands') }}</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <router-link to="/dashboard" class="text-muted">{{ __('dashboard') }}</router-link>
                        </li>
                        <li class="breadcrumb-item active text-primary" aria-current="page">{{ __('brands') }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        <section class="section">
            <div class="figma-main-section-card">
                <div class="card-body p-0">
                    <!-- Modern Filter Action Bar -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 figma-action-bar-row">
                        <div class="flex-grow-1">
                            <div class="figma-search-container">
                                <i class="fa fa-search text-muted"></i>
                                <input v-model="filter" type="text" class="figma-search-input"
                                    :placeholder="__('search') || 'Search...'" @input="getRecords()">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-figma-filter d-flex align-items-center gap-2"
                                @click="edit_record = true">
                                <i class="fa fa-plus"></i>
                                <span>{{ __('add_brand') }}</span>
                            </button>

                            <button class="btn btn-figma-filter d-flex align-items-center gap-2" @click="getRecords()"
                                v-b-tooltip.hover :title="__('refresh')">
                                <i class="fa fa-refresh"></i>
                                <span>{{ __('refresh') || 'Refresh' }}</span>
                            </button>
                        </div>
                    </div>
                    <b-table :items="translatedBrands" :fields="fields" :sort-by.sync="sortBy"
                        :sort-desc.sync="sortDesc" :sort-direction="sortDirection" :bordered="false" :busy="isLoading"
                        stacked="md" show-empty :tbody-tr-class="() => 'figma-tr align-middle'" small
                        class="figma-order-table mb-0">
                        <template #table-busy>
                            <div class="text-center text-black my-2">
                                <b-spinner class="align-middle"></b-spinner>
                                <strong>{{ __('loading') }}...</strong>
                            </div>
                        </template>

                        <template #cell(id)="row">
                            {{ (currentPage - 1) * perPage + row.index + 1 }}
                        </template>

                        <template #cell(image)="row">
                            <p v-if="row.item.image === ''">{{ __('no_image') }}</p>
                            <img :src="$storageUrl + row.item.image" height="50" v-else />
                        </template>

                        <template #cell(status)="row">
                            <span v-if="row.item.status == 1" class="badge bg-success">{{ __('active') }}</span>
                            <span v-else class="badge bg-danger">{{ __('deactive') }}</span>
                        </template>

                        <template #cell(actions)="row">
                            <div class="d-flex gap-2">
                                <button class="figma-action-btn" @click="edit_record = row.item" v-b-tooltip.hover
                                    :title="__('edit')">
                                    <base-icon name="edit icon" hoverName="edit Hover" width="24" height="24" />
                                </button>
                                <button class="figma-action-btn" @click="deleteRecord(row.index, row.item.id)"
                                    v-b-tooltip.hover :title="__('delete')">
                                    <base-icon name="Type=Default" hoverName="Type=Hover" width="24" height="24" />
                                </button>
                            </div>
                        </template>
                    </b-table>
                </div>
                <div class="figma-table-footer">
                    <div class="showing-results-text">
                        {{ __('Showing Result') }} : <span class="showing-bold">{{ pageEnd }}</span> {{
                            __('of') || 'of'
                        }} <span class="showing-bold">{{ totalRows }}</span>
                    </div>
                    <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage" align="right"
                        class="figma-pagination mb-0"></b-pagination>
                </div>
            </div>
        </section>

        <!-- Add / Edit -->
        <app-edit-record v-if="edit_record" :record="edit_record" @modalClose="edit_record = null"
            @saved="onBrandSaved"></app-edit-record>
    </div>
</template>

<script>
import { VuejsDatatableFactory } from 'vuejs-datatable';
import EditRecord from './Edit.vue';

export default {
    components: {
        VuejsDatatableFactory,
        'app-edit-record': EditRecord,
    },
    data() {
        return {
            fields: [
                { key: 'id', label: __('Sr. No.'), class: 'text-center', sortable: true, sortDirection: 'desc' },
                { key: 'name', label: __('name'), class: 'text-center' },
                { key: 'image', label: __('image'), class: 'text-center' },
                {
                    key: 'status', label: __('status'), class: 'text-center', formatter: (value) => {
                        return value == 1 ? __('active') : __('deactive');
                    }
                },
                { key: 'actions', label: __('actions') }
            ],
            totalRows: 0,
            currentPage: 1,
            perPage: this.$perPage || 10,
            pageOptions: this.$pageOptions || [5, 10, 15, 20],
            sortBy: '',
            sortDesc: false,
            sortDirection: 'asc',
            filter: null,
            filterOn: ['id', 'name', 'status'],
            isLoading: false,
            brands: [],
            edit_record: null,
            currentLanguageId: null,
            activeLanguages: []

        }
    },

    computed: {
        translatedBrands() {
            if (!this.currentLanguageId || this.brands.length === 0) {
                return this.brands;
            }

            return this.brands.map(brand => {
                const translatedBrand = { ...brand };

                if (brand.translations && Array.isArray(brand.translations)) {
                    const translation = brand.translations.find(
                        t => Number(t.language_id) === Number(this.currentLanguageId)
                    );
                    if (translation && translation.name && translation.name.trim() !== '') {
                        translatedBrand.name = translation.name;
                    }
                }

                return translatedBrand;
            });
        },
        pageEnd() {
            return Math.min(this.currentPage * this.perPage, this.totalRows);
        }
    }
    ,
    created() {
        this.fetchActiveLanguages().then(() => {
            this.getRecords();
        });
    },
    watch: {
        currentPage() {
            this.getRecords();
        },
        perPage() {
            this.getRecords();
        }
    },
    methods: {


        fetchActiveLanguages() {
            console.log("data fetch");

            return axios.get(this.$apiUrl + '/active_languages')
                .then(response => {
                    this.activeLanguages = response.data.data || [];

                    const appLocale = window.appLocale || 'en';

                    const currentLanguage = this.activeLanguages.find(
                        lang => lang.code === appLocale
                    );

                    if (currentLanguage) {
                        this.currentLanguageId = currentLanguage.id;
                    } else {
                        const defaultLang = this.activeLanguages.find(l => l.is_default === 1);
                        if (defaultLang) {
                            this.currentLanguageId = defaultLang.id;
                        }
                    }

                    console.log('Languages:', this.activeLanguages);
                    console.log('Current language ID:', this.currentLanguageId);

                });
        },

        getRecords() {

            this.isLoading = true;
            axios.get(this.$apiUrl + '/products/brands', {
                params: {
                    page: this.currentPage,
                    per_page: this.perPage,
                    filter: this.filter
                }
            }).then((response) => {
                this.isLoading = false;
                const data = response.data;
                this.brands = data.data;
                this.totalRows = data.total;

            }).catch(() => {
                this.isLoading = false;
            });
        },
        onBrandSaved(message) {
            this.showMessage('success', message);
            this.getRecords();
            this.edit_record = null;
        },
        deleteRecord(index, id) {
            this.$swal.fire({
                title: __('are_you_sure'),
                text: __('you_want_be_able_to_revert_this'),
                confirmButtonText: __('yes_sure'),
                cancelButtonText: __('cancel'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#37a279',
                cancelButtonColor: '#d33',
            }).then(result => {
                if (result.value) {
                    this.isLoading = true;
                    axios.post(this.$apiUrl + '/products/brands/delete', { id })
                        .then((response) => {
                            this.isLoading = false;
                            this.brands.splice(index, 1);
                            this.showMessage('success', response.data.message);
                        }).catch(() => {
                            this.isLoading = false;
                        });
                }
            });
        },
    }
};
</script>
