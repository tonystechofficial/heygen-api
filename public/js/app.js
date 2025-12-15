/**
 * HeyGen Video Generator - Vue.js 3 App
 * Uses Composition API with setup script pattern
 */

const { createApp, ref, reactive, onMounted } = Vue;

const API_BASE = '/api';

createApp({
    setup() {
        // State
        const activeTab = ref('generate');
        const loading = ref(false);
        const generating = ref(false);
        const message = ref(null);

        const avatarGroups = ref([]);
        const avatars = ref([]);
        const voices = ref([]);
        const videos = ref([]);
        const selectedGroup = ref('');

        const syncing = reactive({
            avatars: false,
            voices: false
        });

        const selectedDimension = ref('1920x1080');

        const form = reactive({
            title: '',
            script: '',
            avatar_id: '',
            voice_id: '',
            settings: {
                width: 1920,
                height: 1080,
                background_color: '#FFFFFF',
                background_image_url: null,
                speed: 1.0
            }
        });

        // API helpers
        const api = async (method, endpoint, data = null) => {
            const options = {
                method,
                headers: { 'Content-Type': 'application/json' }
            };
            if (data) options.body = JSON.stringify(data);
            
            const response = await fetch(`${API_BASE}${endpoint}`, options);
            return response.json();
        };

        // Loading states
        const loadingGroups = ref(false);
        const loadingAvatars = ref(false);
        const loadingVoices = ref(false);
        const errors = reactive({ groups: null, avatars: null, voices: null });

        // Load avatar groups
        const loadAvatarGroups = async () => {
            loadingGroups.value = true;
            errors.groups = null;
            try {
                const result = await api('GET', '/avatar-groups');
                if (result.success) {
                    avatarGroups.value = result.data || [];
                } else {
                    errors.groups = result.error || 'Erreur lors du chargement des groupes';
                }
            } catch (e) {
                console.error('Failed to load avatar groups:', e);
                errors.groups = 'Erreur réseau: ' + e.message;
            } finally {
                loadingGroups.value = false;
            }
        };

        // Load avatars by group
        const loadAvatarsByGroup = async (groupId) => {
            if (!groupId) {
                avatars.value = [];
                return;
            }
            loadingAvatars.value = true;
            errors.avatars = null;
            try {
                const result = await api('GET', `/avatar-groups/${groupId}/avatars`);
                if (result.success) {
                    avatars.value = result.data || [];
                } else {
                    errors.avatars = result.error || 'Erreur lors du chargement des avatars';
                }
            } catch (e) {
                console.error('Failed to load avatars:', e);
                errors.avatars = 'Erreur réseau: ' + e.message;
            } finally {
                loadingAvatars.value = false;
            }
        };

        // Handle group selection change
        const onGroupChange = () => {
            form.avatar_id = '';
            loadAvatarsByGroup(selectedGroup.value);
        };

        // Load all avatars (legacy)
        const loadAvatars = async () => {
            loadingAvatars.value = true;
            errors.avatars = null;
            try {
                const result = await api('GET', '/avatars');
                if (result.success) {
                    avatars.value = result.data || [];
                } else {
                    errors.avatars = result.error || 'Erreur lors du chargement des avatars';
                }
            } catch (e) {
                console.error('Failed to load avatars:', e);
                errors.avatars = 'Erreur réseau: ' + e.message;
            } finally {
                loadingAvatars.value = false;
            }
        };

        // Load voices
        const loadVoices = async () => {
            loadingVoices.value = true;
            errors.voices = null;
            try {
                const result = await api('GET', '/voices');
                if (result.success) {
                    voices.value = result.data || [];
                } else {
                    errors.voices = result.error || 'Erreur lors du chargement des voix';
                }
            } catch (e) {
                console.error('Failed to load voices:', e);
                errors.voices = 'Erreur réseau: ' + e.message;
            } finally {
                loadingVoices.value = false;
            }
        };

        // Sync avatars from HeyGen
        const syncAvatars = async () => {
            syncing.avatars = true;
            errors.avatars = null;
            try {
                const result = await api('POST', '/avatars/sync');
                if (result.success) {
                    avatars.value = result.data || [];
                    message.value = { type: 'success', text: result.message || 'Avatars synchronisés' };
                } else {
                    errors.avatars = result.error || 'Erreur lors de la synchronisation';
                }
            } catch (e) {
                errors.avatars = 'Erreur: ' + e.message;
            } finally {
                syncing.avatars = false;
            }
        };

        // Sync voices from HeyGen
        const syncVoices = async () => {
            syncing.voices = true;
            errors.voices = null;
            try {
                const result = await api('POST', '/voices/sync');
                if (result.success) {
                    voices.value = result.data || [];
                    message.value = { type: 'success', text: result.message || 'Voix synchronisées' };
                } else {
                    errors.voices = result.error || 'Erreur lors de la synchronisation';
                }
            } catch (e) {
                errors.voices = 'Erreur: ' + e.message;
            } finally {
                syncing.voices = false;
            }
        };

        // Load videos
        const loadVideos = async () => {
            loading.value = true;
            try {
                const result = await api('GET', '/videos');
                if (result.success) videos.value = result.data;
            } finally {
                loading.value = false;
            }
        };

        // Generate video
        const generateVideo = async () => {
            generating.value = true;
            message.value = null;

            try {
                const result = await api('POST', '/videos/generate', form);
                
                if (result.success) {
                    message.value = {
                        type: 'success',
                        text: `Vidéo créée ! ID: ${result.data.id}. La génération est en cours...`
                    };
                    // Reset form
                    form.title = '';
                    form.script = '';
                } else {
                    message.value = { type: 'error', text: result.error || 'Erreur lors de la génération' };
                }
            } catch (e) {
                message.value = { type: 'error', text: 'Erreur réseau: ' + e.message };
            } finally {
                generating.value = false;
            }
        };

        // Check video status
        const checkStatus = async (id) => {
            const result = await api('GET', `/videos/${id}/status`);
            if (result.success) {
                const index = videos.value.findIndex(v => v.id === id);
                if (index !== -1) videos.value[index] = result.data;
            }
        };

        // Delete video
        const deleteVideo = async (id) => {
            if (!confirm('Supprimer cette vidéo ?')) return;
            const result = await api('DELETE', `/videos/${id}`);
            if (result.success) {
                videos.value = videos.value.filter(v => v.id !== id);
            }
        };

        // Update dimensions from select
        const updateDimensions = () => {
            const [w, h] = selectedDimension.value.split('x').map(Number);
            form.settings.width = w;
            form.settings.height = h;
        };

        // Status label
        const statusLabel = (status) => {
            const labels = {
                pending: '⏳ En attente',
                processing: '🔄 En cours',
                completed: '✅ Terminée',
                failed: '❌ Échec'
            };
            return labels[status] || status;
        };

        // Init
        onMounted(() => {
            loadAvatarGroups();
            loadVoices();
        });

        return {
            activeTab, loading, generating, message,
            avatarGroups, avatars, voices, videos, syncing,
            selectedGroup, loadingGroups, loadingAvatars, loadingVoices, errors,
            form, selectedDimension,
            loadAvatarGroups, loadAvatarsByGroup, onGroupChange,
            loadAvatars, loadVoices, syncAvatars, syncVoices,
            loadVideos, generateVideo, checkStatus, deleteVideo,
            updateDimensions, statusLabel
        };
    }
}).mount('#app');

