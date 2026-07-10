<template>
    <div>
        <div class="page-heading">
            <div class="page-title mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="modern-page-title mb-0">{{ __('manage_categories') }}</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><router-link to="/dashboard" class="text-muted">{{
                                    __('dashboard') }}</router-link></li>
                            <li class="breadcrumb-item active text-primary" aria-current="page">{{
                                __('manage_categories') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <section class="section">
                <div class="figma-main-section-card">
                    <div class="card-body p-0">
                        <div
                            class="d-flex justify-content-between align-items-center flex-wrap gap-2 figma-action-bar-row">
                            <div class="flex-grow-1">
                                <div class="figma-search-container">
                                    <i class="fa fa-search text-muted"></i>
                                    <input v-model="filter" type="text" class="figma-search-input"
                                        :placeholder="__('search')">
                                </div>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <button class="btn btn-figma-filter d-flex align-items-center gap-2"
                                    @click="getCategories()" v-b-tooltip.hover :title="__('refresh')">
                                    <i class="fa fa-refresh"></i>
                                    <span>{{ __('refresh') }}</span>
                                </button>
                                <button class="btn btn-figma-columns d-flex align-items-center gap-2"
                                    @click="create_new = true" v-if="$can('category_create')">
                                    <i class="fa fa-plus"></i>
                                    <span>{{ __('add_category') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive mb-0">
                            <b-table :items="translatedCategories" :fields="fields" :filter="filter"
                                :filter-included-fields="filterOn" :sort-by.sync="sortBy" :sort-desc.sync="sortDesc"
                                show-empty small :empty-text="__('no_records_to_show')"
                                :empty-filtered-text="__('no_records_to_show')" class="mb-0">

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
                                    <img :src="row.item.image_url" height="80" width="80" 
                                        style="object-fit: cover; cursor: pointer; border-radius: 4px;"
                                        @click="openImageModal(row.item.image_url)" />
                                </template>
                                <template #cell(status)="row">
                                    <span class='badge bg-success' v-if="row.item.status == 1">{{ __('activate')
                                    }}</span>
                                    <span class='badge bg-danger' v-if="row.item.status == 0">{{ __('deactivate')
                                    }}</span>
                                </template>
                                <template #cell(actions)="row">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button class="figma-action-btn" @click="edit_record = row.item"
                                            v-if="$can('category_update')" v-b-tooltip.hover :title="__('edit')">
                                            <base-icon name="edit icon" hoverName="edit Hover" width="24" height="24" />
                                        </button>
                                        <button class="figma-action-btn" @click="deleteCategory(row.index, row.item.id)"
                                            v-if="$can('category_delete')" v-b-tooltip.hover :title="__('delete')">
                                            <base-icon name="Type=Default" hoverName="Type=Hover" width="24"
                                                height="24" />
                                        </button>
                                    </div>
                                </template>

                            </b-table>
                        </div>
                        <div class="figma-table-footer flex-wrap gap-3">
                            <div class="showing-results-text small">
                                {{ __('Showing Result') }} : <span class="showing-bold">{{ pageEnd }}</span> {{ __('of')
                                }} <span class="showing-bold">{{ totalRows }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                                    align="right" class="figma-pagination mb-0" @change="getCategories"
                                    hide-goto-end-buttons hide-ellipsis prev-text="<" next-text=">"></b-pagination>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Add / Edit -->
        <app-edit-record v-if="create_new || edit_record" :record="edit_record" @modalClose="hideModal()"
            @saved="onCategorySaved"></app-edit-record>

        <!-- Image Preview Modal -->
        <b-modal ref="image-modal" title="" hide-footer size="lg" centered>
            <div class="text-center">
                <img :src="previewImageUrl" style="max-width: 100%; max-height: 500px;" />
            </div>
        </b-modal>
    </div>

</template>
<script>

import EditRecord from './Edit.vue';
export default {
    components: {
        'app-edit-record': EditRecord,
    },
    data: function () {
        return {
            fields: [
                { key: 'id', label: __('Sr. No.'), class: 'text-center', sortable: true, sortDirection: 'asc' },
                { key: 'name', label: __('name'), class: 'text-center', sortable: true },
                { key: 'subtitle', label: __('subtitle'), class: 'text-center', sortable: true },
                { key: 'image', label: __('image'), class: 'text-center' },
                { key: 'status', label: __('status'), class: 'text-center' },
                { key: 'actions', label: __('actions'), class: 'text-center' }
            ],
            totalRows: 1,
            currentPage: 1,
            perPage: this.$perPage,
            pageOptions: this.$pageOptions,
            sortBy: 'id',
            sortDesc: true,
            sortDirection: 'asc',
            filter: null,
            filterOn: [],
            page: 1,

            categories: [],
            isLoading: false,
            sectionStyle: 'style_1',
            max_visible_categories: 12,
            max_col_in_single_row: 3,
            create_new: null,
            edit_record: null,
            settingModalShow: false,
            currentLanguageId: null,
            activeLanguages: [],
            latestRequestId: 0,
            previewImageUrl: null
        }
    },
    computed: {
        sortOptions() {
            // Create an options list from our fields
            return this.fields
                .filter(f => f.sortable)
                .map(f => {
                    return { text: f.label, value: f.key }
                })
        },
        pageStart() {
            if (this.totalRows === 0) return 0;
            return (this.currentPage - 1) * this.perPage + 1;
        },
        pageEnd() {
            return Math.min(this.currentPage * this.perPage, this.totalRows);
        },
        filteredCategories: function () {
            const list = Array.isArray(this.categories) ? this.categories : [];
            const query = this.filter ? this.filter.toLowerCase() : '';
            return list.filter(category => {
                const name = (category.name || '').toString().toLowerCase();
                const subtitle = (category.subtitle || '').toString().toLowerCase();
                return name.includes(query) || subtitle.includes(query);
            });
        },
        // Computed property to transform categories with translated fields based on current app_locale
        translatedCategories: function () {
            // Guard: ensure categories is an array to avoid "Cannot read properties of undefined (reading 'length')"
            const list = Array.isArray(this.categories) ? this.categories : [];
            if (!this.currentLanguageId || list.length === 0) {
                return list;
            }

            // Transform each category to use translated fields
            return list.map(category => {
                const translatedCategory = { ...category };

                if (category.translations && Array.isArray(category.translations)) {
                    const translation = category.translations.find(
                        t => t.language_id === this.currentLanguageId
                    );

                    // Use translated name if available and not empty, otherwise fallback to main table name
                    if (translation && translation.name && translation.name.trim() !== '') {
                        translatedCategory.name = translation.name;
                    }

                    // Use translated subtitle if available and not empty, otherwise fallback to main table subtitle
                    if (translation && translation.subtitle && translation.subtitle.trim() !== '') {
                        translatedCategory.subtitle = translation.subtitle;
                    }
                }
                return translatedCategory;
            });
        },
    },
    mounted() {
    },
    watch: {
        $route(to, from) {
            this.showCreateModal();
        },
        currentPage(newPage) {
            this.getCategories();
        },
        perPage(newPerPage) {
            this.getCategories();
        },
        filter(newFilter, oldFilter) {
            if (this.currentPage === 1) {
                this.getCategories();
            } else {
                this.currentPage = 1;
            }
        }
    },
    created: function () {
        this.showCreateModal();
        this.fetchActiveLanguages().then(() => {
            this.getCategories();
        });
    },
    methods: {
        fetchActiveLanguages() {
            return axios.get(this.$apiUrl + '/active_languages')
                .then(response => {
                    if (response.data.data && Array.isArray(response.data.data)) {
                        this.activeLanguages = response.data.data;

                        const appLocale = window.appLocale || 'en';

                        // Find language ID for current app_locale code
                        const currentLanguage = this.activeLanguages.find(
                            lang => lang.code === appLocale
                        );

                        if (currentLanguage) {
                            this.currentLanguageId = currentLanguage.id;
                        } else {
                            const defaultLanguage = this.activeLanguages.find(
                                lang => lang.is_default === 1
                            );
                            if (defaultLanguage) {
                                this.currentLanguageId = defaultLanguage.id;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading languages:', error);
                });
        },

        getCategories() {
            this.isLoading = true;
            this.latestRequestId++;
            const currentRequestId = this.latestRequestId;

            const params = {
                offset: this.currentPage,
                limit: this.perPage,
                filter: this.filter,
                status: null, // Explicitly request all categories (active and inactive)
                _t: Date.now()
            };
            axios.get(this.$apiUrl + '/categories', { params })
                .then((response) => {
                    if (currentRequestId !== this.latestRequestId) {
                        return; // Discard stale/out-of-order response
                    }
                    this.isLoading = false;
                    const data = response.data || {};
                    // Always set to array so .length and table empty state work; avoid undefined
                    this.categories = Array.isArray(data.data) ? data.data : [];
                    this.totalRows = typeof data.total === 'number' ? data.total : 0;
                })
                .catch(() => {
                    if (currentRequestId !== this.latestRequestId) {
                        return; // Discard stale/out-of-order response
                    }
                    this.isLoading = false;
                    this.categories = [];
                    this.totalRows = 0;
                });
        },


        deleteCategory(index, id) {
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
                    this.isLoading = true
                    let postData = {
                        id: id
                    }
                    axios.post(this.$apiUrl + '/categories/delete', postData)
                        .then((response) => {
                            this.isLoading = false
                            let data = response.data;
                            this.categories.splice(index, 1)
                            this.showMessage('success', data.message);
                        });
                }
            });
        },
        showCreateModal() {
            let create = this.$route.params.create;
            if (create) {
                this.create_new = true;
            }
        },
        hideModal() {
            this.create_new = false
            this.edit_record = false
            this.$router.push({ path: '/manage_categories' });
        },
        // Called when Edit modal saves; show toast once and refresh list
        onCategorySaved(message) {
            this.showMessage('success', message);
            this.getCategories();
            this.create_new = null;
        },
        openImageModal(imageUrl) {
            this.previewImageUrl = imageUrl;
            this.$refs['image-modal'].show();
        },
    }
};
</script>
