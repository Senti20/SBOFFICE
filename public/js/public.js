// Render Table (Hiding Filename & Doc ID)
function renderPublicDocs(docs) {
    const container = document.getElementById('publicDocList');
    if (!docs || docs.length === 0) {
        container.innerHTML = '<p>No public documents available.</p>';
        return;
    }

    let html = `<table>
        <thead><tr>
            <th>Session Name</th>
            <th>Type</th>
            <th>Session #</th>
            <th>Date</th>
            <th>Sponsors</th>
            <th>Action</th>
        </tr></thead><tbody>`;

    docs.forEach((doc) => {
        const fileUrl = `/storage/${doc.file_path}`; 
        
        html += `<tr>
            <td><strong>${doc.session_name}</strong></td>
            <td>${doc.type}</td>
            <td>${doc.session_id}</td>
            <td>${doc.session_date}</td>
            <td>${doc.sponsors || '-'}</td>
            <td>
                <a href="${fileUrl}" target="_blank" class="btn-sm btn-view" style="text-decoration:none;">Read</a>
                <!-- Request button triggers the form below -->
                <button onclick="openRequestForm('${doc.document_id}', '${doc.session_name}')" class="btn-sm btn-primary">Request</button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// Handle Multiple Filters + OCR Search
function handlePublicSearch() {
    const typeF = document.getElementById('filterType').value.toLowerCase();
    const sessionF = document.getElementById('filterSession').value.toLowerCase();
    const sponsorF = document.getElementById('filterSponsor').value.toLowerCase();
    const query = document.getElementById('publicSearchInput').value.toLowerCase();

    const filteredDocs = allPublicDocs.filter(doc => {
        const mType = !typeF || (doc.type && doc.type.toLowerCase().includes(typeF));
        const mSession = !sessionF || (doc.session_id && doc.session_id.toLowerCase().includes(sessionF));
        const mSponsor = !sponsorF || (doc.sponsors && doc.sponsors.toLowerCase().includes(sponsorF));
        
        const mQuery = !query || 
            (doc.session_name && doc.session_name.toLowerCase().includes(query)) ||
            (doc.tags && doc.tags.toLowerCase().includes(query)) ||
            (doc.extracted_text && doc.extracted_text.toLowerCase().includes(query));

        return mType && mSession && mSponsor && mQuery;
    });

    renderPublicDocs(filteredDocs);
}

// Triggered by the "Request" button on the file
function openRequestForm(docId, sessionName) {
    document.getElementById('requestModal').classList.remove('hidden');
    document.getElementById('requestTitle').innerText = `📥 Request Copy: ${sessionName}`;
    document.getElementById('reqDocId').value = docId;
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}

// Submit with File (Valid ID)
async function submitRequest(e) {
    e.preventDefault();
    
    // Because we are uploading an image (Valid ID), we MUST use FormData, not JSON.
    const formData = new FormData();
    formData.append('requester_name', document.getElementById('reqName').value);
    formData.append('contact_number', document.getElementById('reqContact').value);
    formData.append('document_id', document.getElementById('reqDocId').value);
    formData.append('copies', document.getElementById('reqCopies').value);
    formData.append('reason', document.getElementById('reqReason').value);
    formData.append('pickup_date', document.getElementById('reqDate').value);
    formData.append('valid_id', document.getElementById('reqValidId').files[0]);

    try {
        const response = await fetch('/requests', { // Updated from /api/requests to /requests
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData 
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Request submitted! An SMS has been sent to your number.');
            document.getElementById('requestForm').reset();
            document.getElementById('requestModal').classList.add('hidden');
        } else {
            alert('Submission failed. Please check your inputs.');
        }
    } catch (error) {
        console.error('Request error:', error);
        alert('Error submitting request.');
    }
}