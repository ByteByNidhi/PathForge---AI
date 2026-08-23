@extends('layouts.app')

@section('title', 'AI Studio')
@section('kicker', 'Assistant')
@section('heading', 'AI Studio')
@section('lede', 'Ask PathForge’s career assistant about your next skills, projects, internships, and roadmap progress. Answers use your saved PathForge profile — not data typed into the chat as identity.')

@section('content')
    <div class="pf-studio">
        <aside class="pf-card">
            <dl class="context">
                <dt>Student</dt>
                <dd>{{ $user->name }} · Level {{ $user->level ?? 1 }} · {{ $user->xp ?? 0 }} XP</dd>
                <dt>Career path</dt>
                <dd>{{ $pathName ?? 'No roadmap selected yet' }}</dd>
                <dt>Skills</dt>
                <dd>{{ $skillNames->isEmpty() ? 'None added yet' : $skillNames->implode(', ') }}</dd>
            </dl>
            <div class="chips" aria-label="Starter questions">
                <button type="button" class="chip" data-prompt="What should I learn next?">What should I learn next?</button>
                <button type="button" class="chip" data-prompt="How can I improve my skills?">How can I improve my skills?</button>
                <button type="button" class="chip" data-prompt="What projects should I build?">What projects should I build?</button>
                <button type="button" class="chip" data-prompt="Review my current progress.">Review my progress</button>
                <button type="button" class="chip" data-prompt="What skills am I missing?">What skills am I missing?</button>
                <button type="button" class="chip" data-prompt="Help me prepare for an internship.">Internship prep</button>
            </div>
        </aside>

        <section class="pf-card">
            <div id="log" class="log" role="log" aria-live="polite">
                <div class="msg msg-assistant">
                    <strong>AI Studio</strong>
                    <p>Hi {{ $user->name }}. Ask a career question, or tap a starter prompt above. I will use your PathForge path, skills, and progress to answer.</p>
                </div>
            </div>
            <form id="chat-form">
                <div class="composer">
                    <label for="message" class="visually-hidden" style="position:absolute;left:-9999px;">Your question</label>
                    <textarea id="message" name="message" maxlength="2000" required placeholder="Ask about skills, projects, internships, or your roadmap..."></textarea>
                    <button class="btn" id="send" type="submit">Send</button>
                </div>
                <p id="status" class="status"></p>
            </form>
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const form = document.getElementById('chat-form');
            const input = document.getElementById('message');
            const send = document.getElementById('send');
            const log = document.getElementById('log');
            const status = document.getElementById('status');
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function addMessage(role, text, extraClass) {
                const wrap = document.createElement('div');
                wrap.className = 'msg ' + extraClass;
                const label = document.createElement('strong');
                label.textContent = role;
                const body = document.createElement('p');
                body.textContent = text;
                wrap.appendChild(label);
                wrap.appendChild(body);
                log.appendChild(wrap);
                log.scrollTop = log.scrollHeight;
            }

            document.querySelectorAll('.chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    input.value = chip.getAttribute('data-prompt');
                    input.focus();
                });
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const message = (input.value || '').trim();
                if (!message) {
                    status.textContent = 'Please enter a question.';
                    return;
                }

                addMessage('You', message, 'msg-user');
                input.value = '';
                send.disabled = true;
                status.textContent = 'Thinking…';

                fetch(@json(route('ai-studio.chat')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ message: message })
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, status: response.status, data: data };
                        });
                    })
                    .then(function (result) {
                        if (result.ok && result.data && result.data.reply) {
                            addMessage('AI Studio', result.data.reply, 'msg-assistant');
                            status.textContent = '';
                            return;
                        }

                        var errorText = 'The career assistant could not answer right now. Please try again.';
                        if (result.data && result.data.errors && result.data.errors.message) {
                            errorText = result.data.errors.message[0];
                        } else if (result.data && typeof result.data.message === 'string') {
                            errorText = result.data.message;
                        }
                        addMessage('AI Studio', errorText, 'msg-error');
                        status.textContent = '';
                    })
                    .catch(function () {
                        addMessage('AI Studio', 'Could not reach AI Studio. Check your connection and try again.', 'msg-error');
                        status.textContent = '';
                    })
                    .finally(function () {
                        send.disabled = false;
                    });
            });
        })();
    </script>
@endsection
