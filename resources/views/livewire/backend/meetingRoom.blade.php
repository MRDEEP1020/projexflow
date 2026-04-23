<div class="flex flex-col h-screen bg-[#080c14]" x-data="meetingRoom(@js($livekitJwt), @js(config('services.livekit.url', 'wss://your-livekit-server.com')), @js($isHost))">

    {{-- ── Top bar ─────────────────────────────────────────── --}}
    <div class="flex items-center justify-between px-4 h-14 border-b border-[#1c2e45] bg-[#0e1420] flex-shrink-0">
        <div class="flex items-center gap-2.5">
            <svg width="22" height="22" viewBox="0 0 32 32" fill="none">
                <rect width="32" height="32" rx="6" fill="#7EE8A2" fill-opacity="0.12"/>
                <path d="M6 8h10M6 14h14M6 20h7" stroke="#7EE8A2" stroke-width="2" stroke-linecap="round"/>
                <circle cx="24" cy="22" r="4" stroke="#7EE8A2" stroke-width="2"/>
                <path d="M24 20v2l1.5 1.5" stroke="#7EE8A2" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span class="font-['Syne'] font-bold text-white text-sm">{{ $room->title }}</span>
            <span x-show="connected" class="flex items-center gap-1 text-[10px] text-[#7EE8A2]">
                <span class="w-1.5 h-1.5 rounded-full bg-[#7EE8A2] animate-pulse"></span>
                Live
            </span>
        </div>

        <div class="flex items-center gap-2 text-xs text-[#506070]">
            <span x-text="participantCount + ' participant' + (participantCount !== 1 ? 's' : '')"></span>
            @if($ended)
                <flux:badge size="sm" color="zinc">Meeting ended</flux:badge>
            @endif
        </div>
    </div>

    {{-- ── Main area: video grid ────────────────────────────── --}}
    <div class="flex-1 relative overflow-hidden p-4">

        @if($ended)
            {{-- Post-meeting state --}}
            <div class="flex flex-col items-center justify-center h-full gap-5">
                <div class="w-16 h-16 rounded-2xl bg-[#7EE8A2]/10 border border-[#7EE8A2]/15 flex items-center justify-center">
                    <flux:icon.check-circle class="size-8 text-[#7EE8A2]"/>
                </div>
                <div class="text-center">
                    <h2 class="font-['Syne'] text-xl font-bold text-white">Meeting ended</h2>
                    <p class="text-sm text-[#8da0b8] mt-1">
                        Duration: {{ $room->started_at && $room->ended_at ? $room->started_at->diff($room->ended_at)->format('%h:%I:%S') : '—' }}
                    </p>
                </div>

                @if($room->recordings?->isNotEmpty())
                    @foreach($room->recordings as $rec)
                        @if(!$rec->is_processing)
                            <div class="flex gap-3">
                                @if($rec->recording_url)
                                    <a href="{{ $rec->recording_url }}" target="_blank"
                                       class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0e1420] border border-[#1c2e45] text-sm text-[#dde6f0] hover:border-[#254060]">
                                        <flux:icon.play-circle class="size-4 text-[#7EE8A2]"/>
                                        View recording
                                    </a>
                                @endif
                                @if($rec->transcript_url)
                                    <a href="{{ $rec->transcript_url }}" target="_blank"
                                       class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0e1420] border border-[#1c2e45] text-sm text-[#dde6f0] hover:border-[#254060]">
                                        <flux:icon.document-text class="size-4 text-[#8da0b8]"/>
                                        View transcript
                                    </a>
                                @endif
                            </div>
                        @else
                            <p class="text-xs text-[#506070] flex items-center gap-1.5">
                                <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                                Processing recording… check back shortly.
                            </p>
                        @endif
                    @endforeach
                @else
                    <p class="text-xs text-[#506070]">Recording will be available once processing completes.</p>
                @endif

                <a href="{{ route('backend.bookingInbox') }}" wire:navigate
                   class="text-sm text-[#7EE8A2] hover:underline">← Back to bookings</a>
            </div>
        @else
            {{-- Video grid (managed by Alpine/JS) --}}
            <div id="video-grid"
                 class="w-full h-full grid gap-2"
                 :class="{
                     'grid-cols-1': participantCount <= 1,
                     'grid-cols-2': participantCount === 2,
                     'grid-cols-2': participantCount <= 4 && participantCount > 2,
                     'grid-cols-3': participantCount > 4,
                 }"
                 x-ref="videoGrid">
                {{-- Participant tiles are injected by LiveKit JS SDK --}}
            </div>

            {{-- Connection placeholder before joining --}}
            <div x-show="!connected" class="absolute inset-0 flex flex-col items-center justify-center gap-4">
                <div class="w-16 h-16 rounded-full border-2 border-[#7EE8A2]/30 border-t-[#7EE8A2] animate-spin"></div>
                <p class="text-sm text-[#8da0b8]">Connecting to meeting room…</p>
            </div>
        @endif
    </div>

    {{-- ── Control bar ──────────────────────────────────────── --}}
    @if(!$ended)
        <div class="flex items-center justify-center gap-3 px-4 py-4 bg-[#0e1420] border-t border-[#1c2e45] flex-shrink-0">

            {{-- Microphone --}}
            <button @click="toggleMic()"
                class="w-12 h-12 rounded-full flex items-center justify-center transition-all"
                :class="micEnabled
                    ? 'bg-[#1c2e45] text-[#dde6f0] hover:bg-[#254060]'
                    : 'bg-red-500/20 text-red-400 border border-red-500/30'"
                title="Toggle microphone"
            >
                <svg x-show="micEnabled" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                    <path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/>
                    <path d="M19 10v2a7 7 0 01-14 0v-2"/>
                    <line x1="12" y1="19" x2="12" y2="23"/>
                    <line x1="8" y1="23" x2="16" y2="23"/>
                </svg>
                <svg x-show="!micEnabled" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                    <line x1="1" y1="1" x2="23" y2="23"/>
                    <path d="M9 9v3a3 3 0 005.12 2.12M15 9.34V4a3 3 0 00-5.94-.6"/>
                    <path d="M17 16.95A7 7 0 015 12v-2"/>
                    <line x1="12" y1="19" x2="12" y2="23"/>
                </svg>
            </button>

            {{-- Camera --}}
            <button @click="toggleCam()"
                class="w-12 h-12 rounded-full flex items-center justify-center transition-all"
                :class="camEnabled
                    ? 'bg-[#1c2e45] text-[#dde6f0] hover:bg-[#254060]'
                    : 'bg-red-500/20 text-red-400 border border-red-500/30'"
                title="Toggle camera"
            >
                <flux:icon.video-camera class="size-5"/>
            </button>

            {{-- Screen share --}}
            <button @click="toggleScreen()"
                class="w-12 h-12 rounded-full flex items-center justify-center bg-[#1c2e45] text-[#dde6f0] hover:bg-[#254060] transition-all"
                :class="screenSharing ? 'border border-[#7EE8A2] text-[#7EE8A2]' : ''"
                title="Screen share"
            >
                <flux:icon.computer-desktop class="size-5"/>
            </button>

            @if($isHost)
                {{-- Record toggle (host only) --}}
                <button @click="toggleRecording()"
                    class="w-12 h-12 rounded-full flex items-center justify-center transition-all"
                    :class="recording ? 'bg-red-500 text-white animate-pulse' : 'bg-[#1c2e45] text-[#dde6f0] hover:bg-[#254060]'"
                    title="Toggle recording"
                >
                    <flux:icon.record class="size-5"/>
                </button>
            @endif

            {{-- Leave --}}
            <a href="{{ route('backend.bookingInbox') }}" wire:navigate
               class="w-12 h-12 rounded-full flex items-center justify-center bg-red-500 text-white hover:bg-red-600 transition-all"
               title="Leave meeting"
               @click="leaveRoom()"
            >
                <flux:icon.phone-x-mark class="size-5"/>
            </a>
        </div>
    @endif

</div>

{{-- LiveKit JS SDK --}}
<script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('meetingRoom', (jwt, livekitUrl, isHost) => ({
        room: null,
        connected: false,
        micEnabled: true,
        camEnabled: true,
        screenSharing: false,
        recording: false,
        participantCount: 0,

        async init() {
            if (!jwt || jwt.startsWith('livekit-jwt-')) {
                console.warn('LiveKit JWT not configured:', jwt);
                return;
            }

            try {
                const { Room, RoomEvent, Track } = LivekitClient;

                this.room = new Room({
                    adaptiveStream: true,
                    dynacast: true,
                });

                this.room
                    .on(RoomEvent.ParticipantConnected,    () => this.updateCount())
                    .on(RoomEvent.ParticipantDisconnected, () => this.updateCount())
                    .on(RoomEvent.TrackSubscribed, (track, pub, participant) => {
                        this.attachTrack(track, participant);
                    })
                    .on(RoomEvent.Disconnected, () => {
                        this.connected = false;
                    });

                await this.room.connect(livekitUrl, jwt, {
                    audio: true,
                    video: true,
                });

                this.connected = true;
                this.updateCount();

                // Attach local tracks
                const localParticipant = this.room.localParticipant;
                localParticipant.videoTrackPublications.forEach(pub => {
                    if (pub.track) this.attachTrack(pub.track, localParticipant, true);
                });

            } catch (err) {
                console.error('LiveKit connect error:', err);
            }
        },

        attachTrack(track, participant, isLocal = false) {
            const grid = document.getElementById('video-grid');
            if (!grid || track.kind !== 'video') return;

            let tile = document.getElementById('tile-' + participant.identity);
            if (!tile) {
                tile = document.createElement('div');
                tile.id = 'tile-' + participant.identity;
                tile.className = 'relative rounded-xl overflow-hidden bg-[#131d2e] border border-[#1c2e45]';

                const nameTag = document.createElement('div');
                nameTag.className = 'absolute bottom-2 left-2 text-xs font-medium text-white bg-black/40 px-2 py-0.5 rounded-md';
                nameTag.textContent = (isLocal ? 'You' : participant.identity);

                tile.appendChild(nameTag);
                grid.appendChild(tile);
            }

            const el = track.attach();
            el.style.cssText = 'width:100%;height:100%;object-fit:cover';
            if (isLocal) el.muted = true;
            tile.insertBefore(el, tile.firstChild);
        },

        updateCount() {
            this.participantCount = this.room
                ? this.room.participants.size + 1
                : 0;
        },

        async toggleMic() {
            if (!this.room) return;
            this.micEnabled = !this.micEnabled;
            await this.room.localParticipant.setMicrophoneEnabled(this.micEnabled);
        },

        async toggleCam() {
            if (!this.room) return;
            this.camEnabled = !this.camEnabled;
            await this.room.localParticipant.setCameraEnabled(this.camEnabled);
        },

        async toggleScreen() {
            if (!this.room) return;
            this.screenSharing = !this.screenSharing;
            await this.room.localParticipant.setScreenShareEnabled(this.screenSharing);
        },

        toggleRecording() {
            // Dispatch to Livewire — recording toggle calls LiveKit Egress API
            this.recording = !this.recording;
            // @this.call(this.recording ? 'startRecording' : 'stopRecording');
            console.log('Recording toggle:', this.recording);
        },

        leaveRoom() {
            if (this.room) this.room.disconnect();
        },
    }));
});
</script>
