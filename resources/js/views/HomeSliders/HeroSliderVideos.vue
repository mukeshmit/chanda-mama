<template>
    <div>
        <div class="page-heading">
            <div class="page-heading d-flex justify-content-between align-items-center mb-4">
                <h3 class="modern-page-title mb-0">Manage Hero Slider Videos</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <router-link to="/dashboard" class="text-muted">{{ __('dashboard') }}</router-link>
                        </li>
                        <li class="breadcrumb-item active text-primary" aria-current="page">Manage Hero Slider Videos</li>
                    </ol>
                </nav>
            </div>

            <section class="section">
                <div class="figma-main-section-card">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 figma-action-bar-row">
                            <div class="flex-grow-1">
                                <div class="figma-search-container">
                                    <i class="fa fa-search text-muted"></i>
                                    <input v-model="filter" type="text" class="figma-search-input"
                                        :placeholder="__('search') || 'Search...'">
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-figma-filter d-flex align-items-center gap-2"
                                    @click="create_new = true" v-if="$can('home_slider_image_create')">
                                    <i class="fa fa-plus"></i>
                                    <span>{{ __('add_new') }}</span>
                                </button>
                                <button class="btn btn-figma-filter d-flex align-items-center gap-2"
                                    @click="getVideos()" v-b-tooltip.hover :title="__('refresh')">
                                    <i class="fa fa-refresh"></i>
                                    <span>{{ __('refresh') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <b-table :items="videos" :fields="fields" :current-page="currentPage"
                                :per-page="perPage" :filter="filter" :filter-included-fields="filterOn"
                                :sort-by.sync="sortBy" :sort-desc.sync="sortDesc"
                                :sort-direction="sortDirection" :bordered="false" :busy="isLoading"
                                stacked="md" show-empty :tbody-tr-class="() => 'figma-tr align-middle'"
                                small class="figma-order-table mb-0">
                                <template #table-busy>
                                    <div class="text-center text-black my-2">
                                        <b-spinner class="align-middle"></b-spinner>
                                        <strong>{{ __('loading') }}...</strong>
                                    </div>
                                </template>

                                <template #cell(id)="row">
                                    {{ ((currentPage - 1) * perPage) + row.index + 1 }}
                                </template>

                                <template #cell(video)="row">
                                    <video :src="row.item.video_url" height="70" controls muted playsinline></video>
                                </template>

                                <template #cell(status)="row">
                                    <span v-if="row.item.status === 1" class="badge bg-success">{{ __('active') }}</span>
                                    <span v-else class="badge bg-danger">{{ __('deactive') }}</span>
                                </template>

                                <template #cell(actions)="row">
                                    <div class="d-flex gap-2">
                                        <button class="figma-action-btn" @click="edit_record = row.item"
                                            v-if="$can('home_slider_image_update')" v-b-tooltip.hover
                                            :title="__('edit')">
                                            <base-icon name="edit icon" hoverName="edit Hover" width="24" height="24" />
                                        </button>
                                        <button class="figma-action-btn figma-delete-btn"
                                            @click="deleteVideo(row.index, row.item.id)"
                                            v-if="$can('home_slider_image_delete')" v-b-tooltip.hover
                                            :title="__('delete')">
                                            <base-icon name="Type=Default" hoverName="Type=Hover" width="24" height="24" />
                                        </button>
                                    </div>
                                </template>
                            </b-table>
                        </div>
                    </div>
                    <div class="figma-table-footer">
                        <div class="showing-results-text">
                            {{ __('Showing Result') }} : <span class="showing-bold">{{ pageEnd }}</span>
                            {{ __('of') || 'of' }} <span class="showing-bold">{{ totalRows }}</span>
                        </div>
                        <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                            align="right" class="figma-pagination mb-0"></b-pagination>
                    </div>
                </div>
            </section>
        </div>

        <app-edit-record v-if="create_new || edit_record" :record="edit_record" @modalClose="hideModal()"></app-edit-record>
    </div>
</template>

<script>
import EditRecord from './HeroSliderVideoEdit.vue';
import axios from "axios";

export default {
    components: {
        'app-edit-record': EditRecord,
    },
    data: function () {
        return {
            create_new: false,
            edit_record: null,
            fields: [
                { key: 'id', label: 'Sr. No.', class: 'text-center' },
                { key: 'video', label: 'Video', class: 'text-center' },
                { key: 'slider_url', label: __('link'), class: 'text-center' },
                { key: 'status', label: __('status'), class: 'text-center' },
                { key: 'actions', label: __('actions') }
            ],
            videos: [],
            totalRows: 1,
            currentPage: 1,
            perPage: this.$perPage,
            sortBy: '',
            sortDesc: false,
            sortDirection: 'asc',
            filter: null,
            filterOn: [],
            isLoading: false,
        }
    },
    computed: {
        pageEnd() {
            return Math.min(this.currentPage * this.perPage, this.totalRows);
        },
    },
    created() {
        this._videoSavedHandler = (message) => {
            this.showMessage('success', message);
            this.getVideos();
        };
        this.$eventBus.$on('HeroSliderVideoSaved', this._videoSavedHandler);
        this.getVideos();
    },
    beforeDestroy() {
        this.$eventBus.$off('HeroSliderVideoSaved', this._videoSavedHandler);
    },
    methods: {
        getVideos() {
            this.isLoading = true;
            axios.get(this.$apiUrl + '/hero_slider_videos')
                .then((response) => {
                    this.isLoading = false;
                    this.videos = response.data.data || [];
                    this.totalRows = this.videos.length;
                })
                .catch(() => {
                    this.isLoading = false;
                    this.videos = [];
                    this.totalRows = 0;
                });
        },
        deleteVideo(index, id) {
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
                    axios.post(this.$apiUrl + '/hero_slider_videos/delete', { id })
                        .then((response) => {
                            this.isLoading = false;
                            this.videos.splice(index, 1);
                            this.totalRows = this.videos.length;
                            this.showMessage('success', response.data.message);
                        });
                }
            });
        },
        hideModal() {
            this.create_new = false;
            this.edit_record = null;
        },
    }
}
</script>
