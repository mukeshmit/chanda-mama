<template>
    <b-modal ref="my-modal" :title="modal_title" @hidden="$emit('modalClose')" scrollable no-close-on-backdrop no-fade static>
        <div slot="modal-footer">
            <b-button variant="primary" @click="$refs['dummy_submit'].click()" :disabled="isLoading">{{ __('save') }}
                <b-spinner v-if="isLoading" small label="Spinning"></b-spinner>
            </b-button>
            <b-button variant="secondary" @click="hideModal">{{ __('cancel') }}</b-button>
        </div>

        <form ref="my-form" @submit.prevent="saveRecord">
            <div class="row">
                <div class="form-group">
                    <label>Name <i class="text-danger">*</i></label>
                    <input type="text" class="form-control" v-model="name" placeholder="Enter video name" required>
                </div>

                <div class="form-group">
                    <label>Where to Display <i class="text-danger">*</i></label>
                    <select class="form-control form-select" v-model="display_location" required>
                        <option value="hero_section">Hero Section</option>
                        <option value="home_page">Home Page</option>
                        <option value="category_page">Category Page</option>
                        <option value="product_page">Product Page</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Video <i class="text-danger" v-if="!id">*</i></label>
                    <p class="text-muted">Allowed video: MP4, WEBM, OGG. Max 20 MB.</p>
                    <input type="file" accept="video/mp4,video/webm,video/ogg" v-on:change="handleFileUpload"
                        ref="file_video" class="file-input">
                    <div class="file-input-div bg-gray-100" @click="$refs.file_video.click()"
                        @drop="dropFile" @dragover="$dragoverFile" @dragleave="$dragleaveFile">
                        <template v-if="video && video.name !== ''">
                            <label>{{ __('selected_file_name') }}{{ video.name }}</label>
                        </template>
                        <template v-else>
                            <label><i class="fa fa-cloud-upload-alt fa-2x"></i></label>
                            <label>{{ __('drop_files_here_or_click_to_upload') }}</label>
                        </template>
                    </div>
                    <p v-if="videoError" class="error">{{ videoError }}</p>
                    <div class="row" v-if="video_url">
                        <div class="col-md-12">
                            <video class="custom-slider-image" :src="video_url" controls muted playsinline></video>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>{{ __('status') }}</label>
                    <div class="col-md-9 text-left mt-1">
                        <b-form-radio-group
                            v-model="status"
                            :options="[
                                { text: __('deactive'), 'value': 0 },
                                { text: __('active'), 'value': 1 },
                            ]"
                            buttons
                            button-variant="outline-primary"
                            required
                        ></b-form-radio-group>
                    </div>
                </div>
            </div>
            <button ref="dummy_submit" style="display:none;"></button>
        </form>
    </b-modal>
</template>

<script>
import axios from 'axios';

export default {
    props: ['record'],
    data: function () {
        return {
            isLoading: false,
            id: this.record ? this.record.id : null,
            name: this.record ? this.record.name : "",
            display_location: this.record ? (this.record.display_location || 'hero_section') : 'hero_section',
            video: null,
            video_url: this.record ? this.record.video_url : null,
            status: this.record ? this.record.status : 1,
            videoError: null,
            allowedTypes: ['video/mp4', 'video/webm', 'video/ogg'],
            maxSize: 20 * 1024 * 1024,
        };
    },
    computed: {
        modal_title: function () {
            return (this.id ? __('edit') : __('add')) + ' Hero Slider Video';
        },
    },
    methods: {
        showModal() {
            this.$refs['my-modal'].show();
        },
        hideModal() {
            this.$refs['my-modal'].hide();
        },
        dropFile(event) {
            event.preventDefault();
            this.$refs.file_video.files = event.dataTransfer.files;
            this.handleFileUpload();
            event.currentTarget.classList.add('bg-gray-100');
            event.currentTarget.classList.remove('bg-green-300');
        },
        handleFileUpload() {
            const file = this.$refs.file_video.files[0];
            this.videoError = null;
            if (!file) return;
            if (!this.allowedTypes.includes(file.type)) {
                this.videoError = 'Invalid video type. Please upload MP4, WEBM, or OGG.';
                this.$refs.file_video.value = '';
                return;
            }
            if (file.size > this.maxSize) {
                this.videoError = 'Video must be 20 MB or smaller.';
                this.$refs.file_video.value = '';
                return;
            }
            this.video = file;
            this.video_url = URL.createObjectURL(file);
        },
        saveRecord: function () {
            if (!this.name || this.name.trim() === '') {
                this.showError('Please enter video name.');
                return;
            }
            if (!this.id && !this.video) {
                this.videoError = 'Please upload a hero slider video.';
                return;
            }
            if (this.videoError) return;

            this.isLoading = true;
            const formData = new FormData();
            if (this.id) formData.append('id', this.id);
            formData.append('name', this.name);
            formData.append('display_location', this.display_location);
            if (this.video) formData.append('video', this.video);
            formData.append('status', this.status);

            const url = this.id
                ? this.$apiUrl + '/hero_slider_videos/update'
                : this.$apiUrl + '/hero_slider_videos/save';

            axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(res => {
                const data = res.data;
                if (data.status === 1) {
                    this.$eventBus.$emit('HeroSliderVideoSaved', data.message);
                    this.hideModal();
                } else {
                    this.showError(data.message);
                    this.isLoading = false;
                }
            }).catch(error => {
                this.isLoading = false;
                this.showError(error.message || __('something_went_wrong'));
            });
        }
    },
    mounted() {
        this.showModal();
    }
}
</script>
