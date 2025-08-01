// Playcloud Music Player JavaScript

class MusicPlayer {
    constructor() {
        this.audio = new Audio();
        this.currentTrack = null;
        this.playlist = [];
        this.currentIndex = 0;
        this.isPlaying = false;
        this.volume = 0.7;
        this.isShuffled = false;
        this.isRepeated = false;
        
        this.initializePlayer();
        this.bindEvents();
    }
    
    initializePlayer() {
        // Set initial volume
        this.audio.volume = this.volume;
        
        // Update volume slider
        const volumeSlider = document.querySelector('.volume-slider input');
        if (volumeSlider) {
            volumeSlider.value = this.volume * 100;
        }
    }
    
    bindEvents() {
        // Play/Pause button
        const playPauseBtn = document.querySelector('.play-pause-btn');
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', () => this.togglePlayPause());
        }
        
        // Volume control
        const volumeSlider = document.querySelector('.volume-slider input');
        if (volumeSlider) {
            volumeSlider.addEventListener('input', (e) => {
                this.volume = e.target.value / 100;
                this.audio.volume = this.volume;
            });
        }
        
        // Progress bar
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.nextTrack());
        
        // Play buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.play-btn')) {
                const trackId = e.target.closest('.play-btn').dataset.trackId;
                if (trackId) {
                    this.playTrack(trackId);
                }
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space' && !e.target.matches('input, textarea')) {
                e.preventDefault();
                this.togglePlayPause();
            }
        });
    }
    
    async playTrack(trackId) {
        try {
            const response = await fetch(`api/track.php?id=${trackId}`);
            const trackData = await response.json();
            
            if (trackData.success) {
                this.currentTrack = trackData.track;
                this.audio.src = `uploads/music/${trackData.track.file_path}`;
                this.audio.play();
                this.isPlaying = true;
                this.updateNowPlaying();
                this.logPlayback(trackId);
            }
        } catch (error) {
            console.error('Error playing track:', error);
        }
    }
    
    togglePlayPause() {
        if (this.isPlaying) {
            this.audio.pause();
            this.isPlaying = false;
        } else {
            this.audio.play();
            this.isPlaying = true;
        }
        this.updatePlayPauseButton();
    }
    
    updatePlayPauseButton() {
        const playPauseBtn = document.querySelector('.play-pause-btn i');
        if (playPauseBtn) {
            playPauseBtn.className = this.isPlaying ? 'fas fa-pause' : 'fas fa-play';
        }
    }
    
    updateProgress() {
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar && this.audio.duration) {
            const progress = (this.audio.currentTime / this.audio.duration) * 100;
            progressBar.style.width = `${progress}%`;
        }
    }
    
    updateNowPlaying() {
        if (this.currentTrack) {
            const trackTitle = document.querySelector('.now-playing-bar .fw-bold');
            const trackArtist = document.querySelector('.now-playing-bar .text-muted');
            const trackCover = document.querySelector('.now-playing-cover');
            
            if (trackTitle) trackTitle.textContent = this.currentTrack.title;
            if (trackArtist) trackArtist.textContent = this.currentTrack.artist_name;
            if (trackCover) {
                trackCover.src = `uploads/covers/${this.currentTrack.cover_image || 'default.jpg'}`;
            }
        }
    }
    
    nextTrack() {
        if (this.playlist.length > 0) {
            this.currentIndex = (this.currentIndex + 1) % this.playlist.length;
            this.playTrack(this.playlist[this.currentIndex].id);
        }
    }
    
    previousTrack() {
        if (this.playlist.length > 0) {
            this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.playlist.length - 1;
            this.playTrack(this.playlist[this.currentIndex].id);
        }
    }
    
    async logPlayback(trackId) {
        try {
            await fetch('api/log-playback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ track_id: trackId })
            });
        } catch (error) {
            console.error('Error logging playback:', error);
        }
    }
}

// AI Recommendation System
class AIRecommendation {
    static async getRecommendations() {
        try {
            const response = await fetch('api/ai-recommendations.php');
            const data = await response.json();
            return data.recommendations;
        } catch (error) {
            console.error('Error getting AI recommendations:', error);
            return [];
        }
    }
    
    static async generatePlaylist(preferences) {
        try {
            const response = await fetch('api/generate-playlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(preferences)
            });
            const data = await response.json();
            return data.playlist;
        } catch (error) {
            console.error('Error generating playlist:', error);
            return null;
        }
    }
}

// Search functionality
class SearchManager {
    static async search(query) {
        try {
            const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            return data.results;
        } catch (error) {
            console.error('Error searching:', error);
            return [];
        }
    }
    
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Playlist management
class PlaylistManager {
    static async createPlaylist(name, description = '') {
        try {
            const response = await fetch('api/playlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: name,
                    description: description
                })
            });
            const data = await response.json();
            return data.playlist;
        } catch (error) {
            console.error('Error creating playlist:', error);
            return null;
        }
    }
    
    static async addToPlaylist(playlistId, trackId) {
        try {
            const response = await fetch('api/playlist-track.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    playlist_id: playlistId,
                    track_id: trackId
                })
            });
            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Error adding to playlist:', error);
            return false;
        }
    }
}

// UI Utilities
class UIUtils {
    static showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    static showLoading(element) {
        element.innerHTML = '<div class="loading"></div>';
    }
    
    static hideLoading(element, originalContent) {
        element.innerHTML = originalContent;
    }
    
    static formatDuration(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize music player
    window.musicPlayer = new MusicPlayer();
    
    // Initialize search functionality
    const searchInput = document.querySelector('#search-input');
    if (searchInput) {
        const debouncedSearch = SearchManager.debounce(async (query) => {
            if (query.length > 2) {
                const results = await SearchManager.search(query);
                // Update search results UI
                updateSearchResults(results);
            }
        }, 300);
        
        searchInput.addEventListener('input', (e) => {
            debouncedSearch(e.target.value);
        });
    }
    
    // Initialize AI recommendations
    if (document.querySelector('.ai-recommendations')) {
        loadAIRecommendations();
    }
    
    // Initialize playlist creation
    const createPlaylistBtn = document.querySelector('#create-playlist-btn');
    if (createPlaylistBtn) {
        createPlaylistBtn.addEventListener('click', showCreatePlaylistModal);
    }
});

// Helper functions
async function loadAIRecommendations() {
    const recommendations = await AIRecommendation.getRecommendations();
    const container = document.querySelector('.ai-recommendations');
    if (container && recommendations.length > 0) {
        // Update AI recommendations UI
        updateAIRecommendations(recommendations);
    }
}

function updateSearchResults(results) {
    const resultsContainer = document.querySelector('#search-results');
    if (resultsContainer) {
        resultsContainer.innerHTML = '';
        
        results.forEach(result => {
            const resultElement = document.createElement('div');
            resultElement.className = 'search-result-item';
            resultElement.innerHTML = `
                <div class="d-flex align-items-center p-2">
                    <img src="uploads/covers/${result.cover_image || 'default.jpg'}" 
                         class="me-3" style="width: 40px; height: 40px; border-radius: 4px;">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${result.title}</div>
                        <div class="text-muted">${result.artist_name}</div>
                    </div>
                    <button class="btn btn-sm btn-primary play-btn" data-track-id="${result.id}">
                        <i class="fas fa-play"></i>
                    </button>
                </div>
            `;
            resultsContainer.appendChild(resultElement);
        });
    }
}

function updateAIRecommendations(recommendations) {
    const container = document.querySelector('.ai-recommendations .row');
    if (container) {
        container.innerHTML = '';
        
        recommendations.forEach(track => {
            const trackElement = document.createElement('div');
            trackElement.className = 'col-md-3 col-sm-6 mb-3';
            trackElement.innerHTML = `
                <div class="card bg-dark border-0 track-card">
                    <img src="uploads/covers/${track.cover_image || 'default.jpg'}" 
                         class="card-img-top" alt="Track Cover">
                    <div class="card-body">
                        <h6 class="card-title">${track.title}</h6>
                        <p class="card-text text-muted">${track.artist_name}</p>
                        <button class="btn btn-sm btn-primary play-btn" data-track-id="${track.id}">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(trackElement);
        });
    }
}

function showCreatePlaylistModal() {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Playlist</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="create-playlist-form">
                        <div class="mb-3">
                            <label for="playlist-name" class="form-label">Playlist Name</label>
                            <input type="text" class="form-control bg-dark text-light" id="playlist-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="playlist-description" class="form-label">Description</label>
                            <textarea class="form-control bg-dark text-light" id="playlist-description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-playlist-btn">Create Playlist</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    document.getElementById('save-playlist-btn').addEventListener('click', async () => {
        const name = document.getElementById('playlist-name').value;
        const description = document.getElementById('playlist-description').value;
        
        if (name) {
            const playlist = await PlaylistManager.createPlaylist(name, description);
            if (playlist) {
                UIUtils.showNotification('Playlist created successfully!', 'success');
                modalInstance.hide();
                modal.remove();
            } else {
                UIUtils.showNotification('Error creating playlist', 'danger');
            }
        }
    });
    
    modal.addEventListener('hidden.bs.modal', () => {
        modal.remove();
    });
}