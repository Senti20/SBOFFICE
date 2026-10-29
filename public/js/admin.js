// CSRF Token Helper
const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// TABS
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));

    document.getElementById(tabId).classList.add('active');
    document.querySelector(`.tab[onclick*="${tabId}"]`).classList.add('active');

    if (tabId === 'tabView') loadDocuments();
    if (tabId === 'tabRequests') loadRequests();
}

// LOAD DATA
async function loadData() {
    await loadDocuments();
    await loadRequests();
}

// FETCH DOCUMENTS
async function loadDocuments() {
    try {
        const response = await fetch('/admin/documents', {
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        if (result.success) {
            renderDocs(result.data);
            document.getElementById('totalDocs').textContent = result.data.length;
        }
    } catch (error) {
        console.error('Error loading documents:', error);
    }
}

// FETCH REQUESTS
async function loadRequests() {
    try {
        const response = await fetch('/admin/requests', {
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        if (result.success) {
            renderRequests(result.data);
            updateRequestStats(result.data);
        }
    } catch (error) {
        console.error('Error loading requests:', error);
    }
}

// UPLOAD DOCUMENT
async function handleUpload(e) {
    e.preventDefault();

    const fileInput = document.getElementById('pdfFile');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Please select a PDF file.');
        return;
    }

    const formData = new FormData();
    formData.append('pdf', fileInput.files[0]);
    formData.append('type', document.getElementById('docType').value);
    formData.append('session_name', document.getElementById('sessionName').value);
    formData.append('session_id', document.getElementById('sessionNumber').value);
    formData.append('session_date', document.getElementById('docDate').value);
    formData.append('sponsors', document.getElementById('sponsors').value);
    formData.append('tags', document.getElementById('tags').value);

    try {
        const response = await fetch('/admin/documents', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            const msg = document.getElementById('uploadMsg');
            msg.style.display = 'block';
            msg.innerHTML = `Document uploaded successfully!`;
            
            document.getElementById('uploadForm').reset();
            document.getElementById('docDate').value = new Date().toISOString().split('T')[0];
            
            await loadDocuments();

            setTimeout(() => {
                msg.style.display = 'none';
            }, 5000);
        } else {
            alert('Upload failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('Upload failed. Check console for details.');
    }
}

// RENDER DOCUMENTS
function renderDocs(docs) {
    const container = document.getElementById('docList');

    if (!docs || docs.length === 0) {
        container.innerHTML = '<p style="color:#999;padding:10px;">No documents uploaded yet.</p>';
        return;
    }

    let html = `<table>
        <thead><tr>
            <th>Session Name</th>
            <th>Type</th>
            <th>Session #</th>
            <th>Action</th>
        </tr></thead><tbody>`;

    docs.forEach((doc) => {
        const fileUrl = `/admin/documents/${doc.id}/file`;
        html += `<tr>
            <td><strong>${doc.session_name}</strong></td>
            <td>${doc.type}</td>
            <td>${doc.session_id}</td>
            <td>
                <a href="${fileUrl}" target="_blank" class="btn-sm btn-view" style="text-decoration:none;">View</a>
                <a href="${fileUrl}" download class="btn-sm btn-complete" style="text-decoration:none;">Download</a>
                <button class="btn-sm btn-cancel" onclick="deleteDocument('${doc.id}')">Delete</button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// DELETE DOCUMENT
async function deleteDocument(id) {
    if (confirm(`Are you sure you want to permanently delete this document?`)) {
        try {
            const response = await fetch(`/admin/documents/${id}`, { 
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            if (result.success) {
                await loadDocuments();
            } else {
                alert('Failed to delete document.');
            }
        } catch (error) {
            console.error('Error deleting document:', error);
        }
    }
}

// RENDER REQUESTS
function renderRequests(reqs) {
    const container = document.getElementById('requestList');

    if (!reqs || reqs.length === 0) {
        container.innerHTML = '<p style="color:#999;padding:10px;">No requests yet.</p>';
        return;
    }

    let html = `<table>
        <thead><tr>
            <th>#</th><th>Requester</th><th>Contact</th><th>Valid ID</th><th>Copies</th><th>Reason</th><th>Status</th><th>Action</th>
        </tr></thead><tbody>`;

    reqs.forEach((r) => {
        const badge = {
            'Pending': 'badge-pending',
            'Ready for Pickup': 'badge-ready',
            'Completed': 'badge-completed',
            'Cancelled': 'badge-cancelled'
        }[r.status] || 'badge-pending';

        let actions = '';
        if (r.status === 'Pending') {
            actions = `<button class="btn-sm btn-ready" onclick="updateRequest('${r.id}', 'Ready for Pickup')">Ready</button>
                       <button class="btn-sm btn-cancel" onclick="updateRequest('${r.id}', 'Cancelled')">Cancel</button>`;
        } else if (r.status === 'Ready for Pickup') {
            actions = `<button class="btn-sm btn-complete" onclick="updateRequest('${r.id}', 'Completed')">Complete</button>
                       <button class="btn-sm btn-cancel" onclick="updateRequest('${r.id}', 'Cancelled')">Cancel</button>`;
        } else {
            actions = `<button class="btn-sm btn-cancel" onclick="deleteRequest('${r.id}')">Delete</button>`;
        }

        const idUrl = `/storage/${r.valid_id_path}`;

        html += `<tr>
            <td><strong>${r.request_id}</strong></td>
            <td>${r.requester_name}</td>
            <td>${r.contact_number}</td>
            <td><a href="${idUrl}" target="_blank" class="btn-sm btn-view" style="text-decoration:none;">View ID</a></td>
            <td>${r.copies}</td>
            <td>${r.reason}</td>
            <td><span class="badge ${badge}">${r.status}</span></td>
            <td>${actions}</td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// UPDATE REQUEST STATUS
async function updateRequest(id, status) {
    try {
        const response = await fetch(`/admin/requests/${id}`, {
            method: 'PUT',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status })
        });
        
        const result = await response.json();
        if (result.success) {
            await loadRequests();
        }
    } catch (error) {
        console.error('Error updating request:', error);
    }
}

// DELETE REQUEST
async function deleteRequest(id) {
    if (confirm(`Delete request? This cannot be undone.`)) {
        try {
            const response = await fetch(`/admin/requests/${id}`, { 
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            if (result.success) {
                await loadRequests();
            }
        } catch (error) {
            console.error('Error deleting request:', error);
        }
    }
}

// STATS UPDATER
function updateRequestStats(reqs) {
    document.getElementById('pendingRequests').textContent = reqs.filter(r => r.status === 'Pending').length;
    document.getElementById('readyRequests').textContent = reqs.filter(r => r.status === 'Ready for Pickup').length;
    document.getElementById('completedRequests').textContent = reqs.filter(r => r.status === 'Completed').length;
}

// INIT
document.addEventListener('DOMContentLoaded', function() {
    if(document.getElementById('docDate')) {
        document.getElementById('docDate').value = new Date().toISOString().split('T')[0];
    }
    loadData();
});