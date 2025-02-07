import 'package:flutter/material.dart';

class PhotoDetailsScreen extends StatelessWidget {
  final Map<String, String> image;
  final VoidCallback deleteImage;

  PhotoDetailsScreen({required this.image, required this.deleteImage});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Photo Details'),
        actions: [
          IconButton(
            icon: Icon(Icons.delete),
            onPressed: deleteImage,
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Image.network(image['image_url']!),
            Text('Uploaded by: ${image['email_address']}'),
            Text('Description: ${image['description']}'),
            Text('Severity: ${image['severity']}'),
            Text('Upload Date: ${image['upload_date']}'),
            Text('Location: ${image['location']}'),
          ],
        ),
      ),
    );
  }
}
