import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/config.dart';
import 'screens.dart';

class ImagesScreen extends StatefulWidget {
  @override
  _ImagesScreenState createState() => _ImagesScreenState();
}

class _ImagesScreenState extends State<ImagesScreen> {
  late Future<List<Map<String, String>>> _images;

  @override
  void initState() {
    super.initState();
    _images = _fetchImages();
  }

  Future<List<Map<String, String>>> _fetchImages() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? userId = prefs.getString('user_id');

    if (userId == null) {
      throw Exception('User not logged in');
    }

    final response = await http.get(
      Uri.parse('http://${CONFIG.server}/PRS/flutter_util/get_images_user.php?user_id=$userId'),
    );

    if (response.statusCode == 200) {
      print(response.body); // Debugging line
      List<dynamic> data = json.decode(response.body);
      return data.map<Map<String, String>>((image) => {
        'id': image['id'].toString(),
        'image_url': image['image_url'].toString(),
        'email_address': image['Email_Address'].toString(),
        'description': image['description'].toString(),
        'severity': image['severity'].toString(),
        'upload_date': image['upload_date'].toString(),
        'location': image['location'].toString(),
      }).toList();
    } else {
      throw Exception('Failed to load images');
    }
  }

  Future<void> _deleteImage(String imageId, String imageUrl) async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? userId = prefs.getString('user_id');

    if (userId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('User not logged in')),
      );
      return;
    }

    final response = await http.post(
      Uri.parse('http://${CONFIG.server}/PRS/flutter_util/delete_image.php'),
      headers: {
        'Content-Type': 'application/json',
      },
      body: json.encode({
        'user_id': userId,
        'image_id': imageId,
        'image_url': imageUrl,
      }),
    );

    final responseData = json.decode(response.body);
    if (responseData['success']) {
      setState(() {
        _images = _fetchImages();
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Image deleted successfully')),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to delete image: ${responseData['message']}')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Uploaded Images'),
      ),
      body: FutureBuilder<List<Map<String, String>>>(
        future: _images,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(child: Text('No images found'));
          } else {
            return Padding(
              padding: const EdgeInsets.all(8.0),
              child: GridView.builder(
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  crossAxisSpacing: 8.0,
                  mainAxisSpacing: 8.0,
                  childAspectRatio: 0.55,
                ),
                itemCount: snapshot.data!.length,
                itemBuilder: (context, index) {
                  final image = snapshot.data![index];
                  return InkWell(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => PhotoDetailsScreen(
                            image: image,
                            deleteImage: () => _deleteImage(image['id']!, image['image_url']!),
                          ),
                        ),
                      );
                    },
                    child: Card(
                      elevation: 4.0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10.0),
                      ),
                      child: Column(
                        children: [
                          Expanded(
                            child: ClipRRect(
                              borderRadius: const BorderRadius.only(
                                topLeft: Radius.circular(10.0),
                                topRight: Radius.circular(10.0),
                              ),
                              child: Image.network(
                                image['image_url']!,
                                fit: BoxFit.cover,
                                width: double.infinity,
                              ),
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.all(8.0),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Uploaded by: ${image['email_address']}'),
                                Text('Description: ${image['description']}'),
                                Text('Severity: ${image['severity']}'),
                                Text('Upload Date: ${image['upload_date']}'),
                                Text('Location: ${image['location']}'),
                              ],
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 4.0),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                IconButton(
                                  icon: Icon(Icons.delete, color: Colors.red),
                                  onPressed: () => _deleteImage(image['id']!, image['image_url']!),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            );
          }
        },
      ),
    );
  }
}
