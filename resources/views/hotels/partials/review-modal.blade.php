<dialog
    class="review-dialog"
    aria-labelledby="review-dialog-title"
    x-data="reviewModal({{ $reservation->id }})"
    x-ref="dialog"
    x-init="$watch('open', value => value ? $refs.dialog.showModal() : $refs.dialog.close()); if ({{ $errors->any() && (int) old('reservation_id') === $reservation->id ? 'true' : 'false' }}) open = true"
    @open-review.window="if ($event.detail === reservationId) open = true"
    @close="open = false"
    @click.self="open = false"
>
    <form
        action="{{ route('reviews.store', $reservation) }}"
        method="post"
        enctype="multipart/form-data"
        class="review-form"
        @submit="if (!rating) { $event.preventDefault(); error = 'Pilih rating bintang terlebih dahulu.' }"
    >
        @csrf
        <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
        <header class="review-form-header">
            <div>
                <span class="review-overline">Pengalaman menginap</span>
                <h2 id="review-dialog-title">Beri ulasan</h2>
            </div>
            <button class="review-close" type="button" aria-label="Tutup form ulasan" @click="open = false">×</button>
        </header>

        <div class="review-booking-summary">
            @php
                $reviewImage = $reservation->room->image ?: $reservation->hotel->image;
            @endphp
            @if ($reviewImage)
                <img src="{{ asset('assets/'.$reviewImage) }}" alt="">
            @else
                <span class="review-booking-placeholder" aria-hidden="true">★</span>
            @endif
            <div>
                <strong>{{ $reservation->hotel->name }}</strong>
                <span>{{ $reservation->room->name }} · {{ $reservation->check_in->format('d M') }}–{{ $reservation->check_out->format('d M Y') }}</span>
            </div>
            <span class="review-completed-badge">Pesanan #{{ $reservation->id }} · Selesai</span>
        </div>

        <section class="review-form-section">
            <h3>Bagaimana pengalaman menginap Anda?</h3>
            <div class="review-star-picker" role="group" aria-label="Pilih rating bintang">
                <template x-for="star in 5" :key="star">
                    <button
                        type="button"
                        :class="{ 'is-selected': star <= rating }"
                        :aria-label="`${star} dari 5 bintang`"
                        :aria-pressed="(rating === star).toString()"
                        @click="rating = star"
                    >★</button>
                </template>
                <span class="review-rating-label" x-text="ratingLabels[rating]"></span>
            </div>
            <input type="hidden" name="rating" :value="rating">
        </section>

        <section class="review-form-section">
            <h3>Apa yang paling Anda sukai?</h3>
            <div class="review-tag-list">
                <template x-for="tag in availableTags" :key="tag">
                    <button
                        type="button"
                        class="review-tag"
                        :class="{ 'is-selected': tags.includes(tag) }"
                        :aria-pressed="tags.includes(tag).toString()"
                        @click="toggleTag(tag)"
                        x-text="tag"
                    ></button>
                </template>
                <template x-for="tag in tags" :key="`selected-${tag}`">
                    <input type="hidden" name="tags[]" :value="tag">
                </template>
            </div>
        </section>

        <details class="review-aspect-details">
            <summary>Beri nilai untuk aspek tertentu <span>(opsional)</span></summary>
            <div class="review-aspect-fields">
                @foreach (['cleanliness' => 'Kebersihan', 'comfort' => 'Kenyamanan', 'location' => 'Lokasi', 'service' => 'Pelayanan'] as $aspect => $label)
                    <div class="review-aspect-field">
                        <span>{{ $label }}</span>
                        <div class="review-aspect-stars" role="group" aria-label="Rating {{ strtolower($label) }}">
                            <template x-for="star in 5" :key="star">
                                <button
                                    type="button"
                                    :class="{ 'is-selected': star <= aspectRatings.{{ $aspect }} }"
                                    :aria-label="`${star} dari 5 bintang untuk {{ strtolower($label) }}`"
                                    :aria-pressed="(aspectRatings.{{ $aspect }} === star).toString()"
                                    @click="aspectRatings.{{ $aspect }} = star"
                                >★</button>
                            </template>
                        </div>
                        <input type="hidden" name="{{ $aspect }}" :value="aspectRatings.{{ $aspect }} || ''">
                    </div>
                @endforeach
            </div>
        </details>

        <label class="review-form-section review-comment-field">
            <span class="review-section-title">Ceritakan pengalaman Anda <small>(opsional)</small></span>
            <textarea
                name="comment"
                maxlength="500"
                rows="4"
                placeholder="Contoh: kamar bersih, pemandangan taman menenangkan, sarapan lengkap..."
                x-model="comment"
            ></textarea>
            <small class="review-character-count"><span x-text="comment.length"></span>/500</small>
        </label>

        <section class="review-form-section">
            <div class="review-upload-heading">
                <h3>Foto &amp; video <span>(opsional)</span></h3>
                <small><span x-text="photos.length + videos.length"></span>/6 file</small>
            </div>
            <div
                class="review-upload-dropzone"
                @dragover.prevent
                @drop.prevent="addDroppedFiles($event.dataTransfer.files)"
            >
                <input class="review-file-input" x-ref="photoInput" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple @change="setFiles('photos', $event.target.files)">
                <input class="review-file-input" x-ref="videoInput" type="file" name="videos[]" accept="video/mp4,video/webm" @change="setFiles('videos', $event.target.files)">
                <span class="review-upload-icon" aria-hidden="true">↥</span>
                <div class="review-upload-copy">
                    <strong>Tarik file ke sini atau pilih dari perangkat</strong>
                    <span>Maks. 5 foto (10 MB/foto) dan 1 video (50 MB, MP4/WebM).</span>
                </div>
                <div class="review-upload-actions">
                    <button class="review-tag" type="button" @click="$refs.photoInput.click()">+ Foto</button>
                    <button class="review-tag" type="button" @click="$refs.videoInput.click()">+ Video</button>
                </div>
            </div>
            <ul class="review-file-list" x-show="photos.length + videos.length">
                <template x-for="(file, index) in photos" :key="file.key">
                    <li>
                        <span x-text="file.name"></span>
                        <button type="button" :aria-label="`Hapus ${file.name}`" @click="removeFile('photos', index)">×</button>
                    </li>
                </template>
                <template x-for="(file, index) in videos" :key="file.key">
                    <li>
                        <span x-text="file.name"></span>
                        <button type="button" :aria-label="`Hapus ${file.name}`" @click="removeFile('videos', index)">×</button>
                    </li>
                </template>
            </ul>
        </section>

        <p class="review-form-error" role="alert" x-show="error" x-text="error"></p>
        @if ($errors->any() && (int) old('reservation_id') === $reservation->id)
            <div class="review-form-error" role="alert">{{ $errors->first() }}</div>
        @endif
        <footer class="review-form-footer">
            <button class="button review-cancel-button" type="button" @click="open = false">Nanti saja</button>
            <button class="button primary review-submit-button" type="submit">Kirim ulasan</button>
        </footer>
    </form>
</dialog>
